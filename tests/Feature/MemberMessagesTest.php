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
        Cache::forget('course-certificates-table-v4');
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

        $this->actingAs($a, 'member')->get('/account')->assertSee('Certificate of Completion')->assertSee('being prepared')->assertDontSee($ca->ctn);   // account page: short note only
        $this->get('/account/course')->assertSee('Certificate number (CTN)')->assertSee($ca->ctn)->assertDontSee($cb->ctn);
        $this->get('/account/course')->assertSee($ca->ctn)->assertSee('course completed 19 November 2026');

        $this->actingAs($owner, 'web')->post('/admin/members/'.$b->id.'/certificate', ['action' => 'revoke'])->assertRedirect();
        $this->assertNull(\App\Support\CourseCertificates::for($b->id));
    }

    public function test_certificate_preview_payment_reference_and_download_only_when_paid(): void
    {
        Cache::forget('course-certificates-table-v4');
        @mkdir(\App\Support\CourseCertificates::dir(), 0775, true);
        $m = Member::query()->create(['name' => 'Warren', 'email' => 'wc@example.com', 'password' => 'a long test password', 'status' => 'active']);
        $owner = User::query()->create(['name' => 'Owner', 'email' => 'owner@jflsolar.test', 'password' => 'a long test password', 'role' => 'owner', 'active' => true, 'must_change_password' => false]);
        $png = function () { $im = imagecreatetruecolor(1200, 800); imagefill($im, 0, 0, imagecolorallocate($im, 250, 250, 250)); $t = tempnam(sys_get_temp_dir(), 'c').'.png'; imagepng($im, $t);
            return new \Illuminate\Http\UploadedFile($t, 'cert.png', 'image/png', null, true); };
        $pdfPath = tempnam(sys_get_temp_dir(), 'p'); file_put_contents($pdfPath, "%PDF-1.4\n%test\n");
        $pdf = new \Illuminate\Http\UploadedFile($pdfPath, 'cert.pdf', 'application/pdf', null, true);

        $this->actingAs($owner, 'web')->post('/admin/members/'.$m->id.'/certificate', ['action' => 'issue']);
        $this->post('/admin/members/'.$m->id.'/certificate', ['action' => 'upload', 'image' => $png(), 'image2' => $png(), 'pdf' => $pdf])->assertSessionHas('status', 'Certificate uploaded.');
        $this->get('/admin/members/'.$m->id.'/certificate/thumb.jpg?page=2')->assertOk();
        $this->post('/admin/course/certificate-qr', ['qr' => $png()])->assertSessionHas('status');
        $this->get('/admin/members/'.$m->id.'/certificate/thumb.jpg')->assertOk()->assertHeader('Content-Type', 'image/jpeg');

        session()->forget('member_pw');
        $this->actingAs($m, 'member')->get('/account')->assertSee('Certificate of Completion – Ready for download')->assertDontSee('Pay via GCash, and you can download it');
        $this->get('/account/course')->assertSee('Certificate of Completion is ready')->assertSee('To download your certificate, pay ₱2,500 via GCash')->assertSee('Type the GCash reference number exactly')->assertSee('/account/certificate/gcash-qr.jpg', false)->assertDontSee('Download your certificate</a>', false);
        $this->get('/account/certificate/thumb.jpg')->assertOk();
        $this->get('/account/course')->assertSee('Lessons page (preview)')->assertSee('Certificate (preview)');
        $this->get('/account/certificate/gcash-qr.jpg')->assertOk();
        $this->get('/account/certificate/download')->assertRedirect('/account#course');
        $this->post('/account/certificate/reference', ['reference' => '1234 567 890123'])->assertRedirect('/account#course');
        $this->get('/account/course')->assertSee('We received your reference number');

        $this->actingAs($owner, 'web')->get('/admin/course')->assertSee('Payments to check in GCash')->assertSee('1234 567 890123');
        $this->post('/admin/members/'.$m->id.'/certificate', ['action' => 'paid']);

        session()->forget('member_pw');
        $this->actingAs($m, 'member')->get('/account')->assertSee('Download your certificate')->assertSee('Your payment is confirmed.');
        $this->get('/account/course')->assertDontSee('Your certificate of completion (sample)')->assertSee('Certificate number (CTN)')->assertSee('href="/account/certificate/download" title="Download your certificate"', false)->assertSee('Lessons page');
        $r = $this->get('/account/certificate/download')->assertOk();
        $this->assertStringContainsString('Certificate-JFL-', (string) $r->headers->get('content-disposition'));

        auth('member')->logout();
        auth('web')->logout();
        session()->flush();
        $this->get('/account/certificate/thumb.jpg')->assertNotFound();
        $this->get('/account/certificate/gcash-qr.jpg')->assertNotFound();
        \App\Support\CourseCertificates::revoke($m->id);
        @unlink(\App\Support\CourseCertificates::qrFile());
    }

    public function test_fake_reference_numbers_get_a_pop_up_warning_and_block_after_three(): void
    {
        Cache::forget('course-certificates-table-v4');
        @mkdir(\App\Support\CourseCertificates::dir(), 0775, true);
        $m = Member::query()->create(['name' => 'Fake', 'email' => 'fk@example.com', 'password' => 'a long test password', 'status' => 'active']);
        $owner = User::query()->create(['name' => 'Owner', 'email' => 'owner@jflsolar.test', 'password' => 'a long test password', 'role' => 'owner', 'active' => true, 'must_change_password' => false]);
        $pdfPath = tempnam(sys_get_temp_dir(), 'p'); file_put_contents($pdfPath, "%PDF-1.4\n");
        $this->actingAs($owner, 'web')->post('/admin/members/'.$m->id.'/certificate', ['action' => 'issue']);
        $this->post('/admin/members/'.$m->id.'/certificate', ['action' => 'upload', 'pdf' => new \Illuminate\Http\UploadedFile($pdfPath, 'c.pdf', 'application/pdf', null, true)]);

        for ($i = 1; $i <= 3; $i++) {
            session()->forget('member_pw');
            $this->actingAs($m, 'member')->post('/account/certificate/reference', ['reference' => '99999990000'.$i])->assertSessionHas('status');
            $this->actingAs($owner, 'web')->get('/admin/members/'.$m->id)->assertSee('Reject: no GCash payment with this reference');
            $this->post('/admin/members/'.$m->id.'/certificate', ['action' => 'reject']);
            session()->forget('member_pw');
            $page = $this->actingAs($m, 'member')->get('/account/course')->assertSee('Warning '.$i.' of 3')->assertSee('99999990000'.$i);
            $i < 3 ? $page->assertSee('GCash reference number</span>', false) : $page->assertSee('you can no longer send one here');
            $this->post('/account/certificate/warning-seen');
            $this->get('/account/course')->assertDontSee('Warning '.$i.' of 3');
        }
        $this->post('/account/certificate/reference', ['reference' => '1234567890123'])->assertSessionHas('status_error');
        $this->assertNull(\App\Support\CourseCertificates::for($m->id)->gcash_ref);

        $this->actingAs($owner, 'web')->get('/admin/members/'.$m->id)->assertSee('blocked from sending more');
        $this->post('/admin/members/'.$m->id.'/certificate', ['action' => 'reset-wrong']);
        $this->assertSame(0, (int) \App\Support\CourseCertificates::for($m->id)->wrong_refs);
        \App\Support\CourseCertificates::revoke($m->id);
    }

    public function test_account_menu_ends_with_log_out(): void
    {
        $m = Member::query()->create(['name' => 'Menu', 'email' => 'menu@example.com', 'password' => 'a long test password', 'status' => 'active']);
        $html = (string) $this->actingAs($m, 'member')->get('/account')->getContent();
        $menu = substr($html, (int) strpos($html, 'aria-label="Your account" class="rounded'));
        $menu = substr($menu, 0, (int) strpos($menu, '</ul>'));
        $this->assertStringEndsWith('Log out</button></form></li>', trim($menu));
        $this->post('/logout')->assertRedirect();
        $this->assertNull(\App\Support\Members::current());
    }

    public function test_staff_can_mark_all_lessons_passed_for_a_member(): void
    {
        \App\Support\Course::ensureTables();
        foreach ([1, 2] as $i) {
            \Illuminate\Support\Facades\DB::table('course_lessons')->insert(['slug' => 'mp-'.$i, 'title' => 'MP lesson '.$i, 'summary' => 's', 'body' => '<p>x</p><details><summary>Q1?</summary>A1</details><details><summary>Q2?</summary>A2</details>', 'position' => 100 + $i, 'status' => 'published', 'created_at' => now(), 'updated_at' => now()]);
        }
        $m = Member::query()->create(['name' => 'Tester', 'email' => 'mp@example.com', 'password' => 'a long test password', 'status' => 'active']);
        $owner = User::query()->create(['name' => 'Owner', 'email' => 'owner@jflsolar.test', 'password' => 'a long test password', 'role' => 'owner', 'active' => true, 'must_change_password' => false]);
        $this->actingAs($owner, 'web')->get('/admin/members/'.$m->id)->assertSee('Mark all lessons as passed');
        $this->post('/admin/members/'.$m->id.'/certificate', ['action' => 'mark-all-passed'])->assertSessionHas('status');
        $rec = collect(\App\Support\CourseResults::record($m->id));
        $this->assertTrue($rec->isNotEmpty());
        $this->assertTrue($rec->every(fn ($r) => $r['result']['status'] === 'passed'));
        $this->post('/admin/members/'.$m->id.'/certificate', ['action' => 'reset-course'])->assertSessionHas('status');
        $this->assertSame([], \App\Support\CourseResults::record($m->id));
        $this->assertFalse(\App\Support\CourseResults::started($m->id));
        $first = \App\Support\Course::lessons()->first();
        $this->post('/admin/members/'.$m->id.'/certificate', ['action' => 'mark-lesson-passed', 'lesson' => $first->id])->assertSessionHas('status');
        $rec = collect(\App\Support\CourseResults::record($m->id));
        $this->assertSame([$first->id], $rec->pluck('lesson.id')->all());
        $this->assertSame('passed', $rec->first()['result']['status']);
    }
}
