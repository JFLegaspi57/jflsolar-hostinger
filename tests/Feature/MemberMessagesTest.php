<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MemberMessagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Cache::forget('member-messages-table-v1');
    }

    public function test_staff_post_a_message_that_only_that_member_sees_until_closed(): void
    {
        $warren = Member::query()->create(['name' => 'Warren', 'email' => 'warren@example.com', 'password' => 'a long test password', 'status' => 'active']);
        $other = Member::query()->create(['name' => 'Other', 'email' => 'other@example.com', 'password' => 'a long test password', 'status' => 'active']);
        $owner = User::query()->create(['name' => 'Owner', 'email' => 'owner@jflsolar.test', 'password' => 'a long test password', 'role' => 'owner', 'active' => true, 'must_change_password' => false]);

        $this->actingAs($owner, 'web')->post('/admin/members/'.$warren->id.'/messages', ['title' => 'A personal message from JF Legaspi', 'body' => "Hi Warren,\nCongratulations!\n<script>x</script>"])
            ->assertRedirect('/admin/members/'.$warren->id.'#messages');
        $this->get('/admin/members/'.$warren->id)->assertSee('Showing on the account page (not closed yet)');

        $this->actingAs($warren, 'member')->get('/account')->assertSee('A personal message from JF Legaspi')->assertSee('Congratulations!')->assertDontSee('<script>x</script>', false);
        session()->forget('member_pw');   // a fresh sign-in for the other member
        $this->actingAs($other, 'member')->get('/account')->assertDontSee('A personal message from JF Legaspi');

        $id = \App\Support\MemberMessages::all($warren->id)->first()->id;
        $this->actingAs($other, 'member')->post('/account/messages/'.$id.'/close');   // not theirs: nothing happens
        session()->forget('member_pw');
        $this->actingAs($warren, 'member')->get('/account')->assertSee('A personal message from JF Legaspi');
        $this->post('/account/messages/'.$id.'/close')->assertRedirect('/account');
        $this->get('/account')->assertDontSee('A personal message from JF Legaspi');

        $this->actingAs($owner, 'web')->get('/admin/members/'.$warren->id)->assertSee('Read and closed by the member');
        $this->post('/admin/members/'.$warren->id.'/messages/'.$id.'/delete')->assertRedirect();
        $this->assertCount(0, \App\Support\MemberMessages::all($warren->id));
    }

    public function test_course_students_see_the_own_words_note_with_their_first_name(): void
    {
        $student = Member::query()->create(['name' => 'WARREN Canaya', 'email' => 'w@example.com', 'password' => 'a long test password', 'status' => 'active']);
        $new = Member::query()->create(['name' => 'Not Started', 'email' => 'n@example.com', 'password' => 'a long test password', 'status' => 'active']);
        \App\Support\CourseResults::ensureTable();
        \Illuminate\Support\Facades\DB::table('course_lesson_views')->insert(['member_id' => $student->id, 'lesson_id' => 1, 'times' => 1, 'first_at' => now(), 'last_at' => now()]);

        $this->actingAs($student, 'member')->get('/account')->assertSee('A little note from Prof. JF')->assertSee('Hi Warren,')->assertSee('use your own words');
        session()->forget('member_pw');
        $this->actingAs($new, 'member')->get('/account')->assertDontSee('A little note from Prof. JF');
    }

    public function test_staff_issue_a_unique_certificate_number_shown_on_the_account_page(): void
    {
        Cache::forget('course-certificates-table-v1');
        $a = Member::query()->create(['name' => 'Warren', 'email' => 'wa@example.com', 'password' => 'a long test password', 'status' => 'active']);
        $b = Member::query()->create(['name' => 'Maria', 'email' => 'ma@example.com', 'password' => 'a long test password', 'status' => 'active']);
        $owner = User::query()->create(['name' => 'Owner', 'email' => 'owner@jflsolar.test', 'password' => 'a long test password', 'role' => 'owner', 'active' => true, 'must_change_password' => false]);

        $this->actingAs($owner, 'web')->get('/admin/members/'.$a->id)->assertSee('Issue certificate number (CTN)');
        $this->post('/admin/members/'.$a->id.'/certificate', ['action' => 'issue', 'completed_on' => '2026-11-19'])->assertRedirect();
        $this->post('/admin/members/'.$a->id.'/certificate', ['action' => 'issue'])->assertRedirect();   // twice: keeps the same number
        $this->post('/admin/members/'.$b->id.'/certificate', ['action' => 'issue'])->assertRedirect();
        $ca = \App\Support\CourseCertificates::for($a->id);
        $cb = \App\Support\CourseCertificates::for($b->id);
        $this->assertMatchesRegularExpression('/^JFL-\d{4}-0001-[A-HJ-NP-Z2-9]{3}$/', $ca->ctn);
        $this->assertMatchesRegularExpression('/^JFL-\d{4}-0002-[A-HJ-NP-Z2-9]{3}$/', $cb->ctn);
        $this->assertSame('2026-11-19', (string) $ca->completed_on);
        $this->get('/admin/members/'.$a->id)->assertSee($ca->ctn)->assertSee('Remove the certificate number');

        $this->actingAs($a, 'member')->get('/account')->assertSee('Certificate number (CTN)')->assertSee($ca->ctn)->assertDontSee($cb->ctn)->assertSee('course completed 19 November 2026');

        $this->actingAs($owner, 'web')->post('/admin/members/'.$b->id.'/certificate', ['action' => 'revoke'])->assertRedirect();
        $this->assertNull(\App\Support\CourseCertificates::for($b->id));
    }
}
