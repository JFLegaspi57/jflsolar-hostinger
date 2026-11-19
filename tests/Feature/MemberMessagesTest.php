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

    public function test_certificate_preview_payment_reference_and_download_only_when_paid(): void
    {
        Cache::forget('course-certificates-table-v2');
        @mkdir(\App\Support\CourseCertificates::dir(), 0775, true);
        $m = Member::query()->create(['name' => 'Warren', 'email' => 'wc@example.com', 'password' => 'a long test password', 'status' => 'active']);
        $owner = User::query()->create(['name' => 'Owner', 'email' => 'owner@jflsolar.test', 'password' => 'a long test password', 'role' => 'owner', 'active' => true, 'must_change_password' => false]);
        $png = function () { $im = imagecreatetruecolor(1200, 800); imagefill($im, 0, 0, imagecolorallocate($im, 250, 250, 250)); $t = tempnam(sys_get_temp_dir(), 'c').'.png'; imagepng($im, $t);
            return new \Illuminate\Http\UploadedFile($t, 'cert.png', 'image/png', null, true); };
        $pdfPath = tempnam(sys_get_temp_dir(), 'p'); file_put_contents($pdfPath, "%PDF-1.4\n%test\n");
        $pdf = new \Illuminate\Http\UploadedFile($pdfPath, 'cert.pdf', 'application/pdf', null, true);

        $this->actingAs($owner, 'web')->post('/admin/members/'.$m->id.'/certificate', ['action' => 'issue']);
        $this->post('/admin/members/'.$m->id.'/certificate', ['action' => 'upload', 'image' => $png(), 'pdf' => $pdf])->assertSessionHas('status', 'Certificate uploaded.');
        $this->post('/admin/course/certificate-qr', ['qr' => $png()])->assertSessionHas('status');
        $this->get('/admin/members/'.$m->id.'/certificate/thumb.jpg')->assertOk()->assertHeader('Content-Type', 'image/jpeg');

        session()->forget('member_pw');
        $this->actingAs($m, 'member')->get('/account')->assertSee('To download your certificate, pay ₱2,500 via GCash')->assertSee('Pay ₱2,500 with GCash')->assertSee('/account/certificate/gcash-qr.jpg', false)->assertDontSee('Download your certificate</a>', false);
        $this->get('/account/certificate/thumb.jpg')->assertOk();
        $this->get('/account/certificate/gcash-qr.jpg')->assertOk();
        $this->get('/account/certificate/download')->assertRedirect('/account#course');
        $this->post('/account/certificate/reference', ['reference' => '1234 567 890123'])->assertRedirect('/account#course');
        $this->get('/account')->assertSee('We received your reference number');

        $this->actingAs($owner, 'web')->get('/admin/course')->assertSee('Payments to check in GCash')->assertSee('1234 567 890123');
        $this->post('/admin/members/'.$m->id.'/certificate', ['action' => 'paid']);

        session()->forget('member_pw');
        $this->actingAs($m, 'member')->get('/account')->assertSee('Download your certificate')->assertSee('Paid via GCash');
        $this->get('/account/course')->assertSee('Certificate number (CTN)')->assertSee('href="/account/certificate/download" title="Download your certificate"', false);
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
}
