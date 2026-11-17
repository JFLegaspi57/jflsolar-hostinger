<?php

namespace Tests\Feature;

use App\Mail\MemberWelcome;
use App\Models\Member;
use App\Models\User;
use App\Support\WelcomeEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WelcomeEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Cache::flush();
        config(['mail.default' => 'array']);
    }

    private function signup(string $email, bool $promos = true): void
    {
        $this->post('/signup', ['name' => 'Juan Dela Cruz', 'email' => $email, 'phone' => '', 'password' => 'a long test password 123', 'password_confirmation' => 'a long test password 123', 'agree' => '1', 'newsletter' => $promos ? '1' : '']);
    }

    public function test_new_members_get_the_welcome_email_once(): void
    {
        Mail::fake();
        $this->signup('juan@example.com');

        Mail::assertSent(MemberWelcome::class, fn ($m) => $m->hasTo('juan@example.com'));
        $member = Member::query()->where('email', 'juan@example.com')->first();
        $this->assertNotNull($member->fresh()->welcome_sent_at);

        // Never twice.
        $this->assertFalse(WelcomeEmail::send($member->fresh()));
        Mail::assertSentCount(1);
    }

    public function test_it_can_be_sent_from_a_gmail_account(): void
    {
        config(['mail.default' => 'log', 'mail.mailers.gmail.username' => 'team@gmail.test', 'mail.mailers.gmail.password' => 'app-password']);
        $this->assertTrue(WelcomeEmail::viaGmail());
        $this->assertTrue(WelcomeEmail::ready());   // even with the website's own mailbox off
        $this->assertSame('team@gmail.test', WelcomeEmail::fromAddress());
        Mail::fake();
        $this->signup('gmailer@example.com');
        Mail::assertSent(MemberWelcome::class, fn ($m) => $m->hasTo('gmailer@example.com') && $m->mailer === 'gmail' && $m->hasFrom('team@gmail.test'));

        $this->actingAs(User::query()->create(['name' => 'Owner', 'email' => 'owner@jflsolar.test', 'password' => 'a long test password', 'role' => 'owner', 'active' => true, 'must_change_password' => false]), 'web');
        $this->get('/admin/subscribers')->assertOk()->assertSee('team@gmail.test')->assertSee('(Gmail)');
    }

    public function test_owner_can_mark_members_who_already_got_it_by_hand(): void
    {
        config(['mail.default' => 'log']);   // mailbox off: sign-ups stay waiting
        $this->signup('done@example.com');
        $this->signup('wait@example.com');
        $this->assertSame(2, WelcomeEmail::pendingCount());
        $this->postJson('/admin/subscribers/welcome/mark', ['emails' => ['done@example.com']])->assertUnauthorized();
        $this->actingAs(User::query()->create(['name' => 'Owner', 'email' => 'owner@jflsolar.test', 'password' => 'a long test password', 'role' => 'owner', 'active' => true, 'must_change_password' => false]), 'web');
        $this->postJson('/admin/subscribers/welcome/mark', ['emails' => ['DONE@example.com', 'nobody@example.com', 'not-an-email']])->assertOk()->assertExactJson(['marked' => 1, 'pending' => 1]);
        $this->assertNotNull(Member::query()->where('email', 'done@example.com')->value('welcome_sent_at'));
        $this->assertNull(Member::query()->where('email', 'wait@example.com')->value('welcome_sent_at'));
    }

    public function test_owner_sees_a_mail_settings_check_without_the_password(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.username' => 'info@jflsolar.test', 'mail.mailers.smtp.password' => 'Secret#Pass1 ', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1]);
        $this->actingAs(User::query()->create(['name' => 'Owner', 'email' => 'owner@jflsolar.test', 'password' => 'a long test password', 'role' => 'owner', 'active' => true, 'must_change_password' => false]), 'web');
        $this->get('/admin/subscribers')->assertOk()->assertSee('Check the mail settings')->assertSee('13 characters (from the settings file), starts or ends with a space')->assertDontSee('Secret#Pass1');
        $this->post('/admin/subscribers/mail-test')->assertRedirect('/admin/subscribers')->assertSessionHas('status_error');
    }

    public function test_owner_saves_the_mailbox_password_in_the_admin(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.username' => 'info@jflsolar.test', 'mail.mailers.smtp.password' => 'old', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1]);
        \App\Support\MailPassword::forget();
        $staff = User::query()->create(['name' => 'Staff', 'email' => 'staff@jflsolar.test', 'password' => 'a long test password', 'role' => 'staff', 'active' => true, 'must_change_password' => false]);
        $this->actingAs($staff, 'web')->post('/admin/subscribers/mail-password', ['password' => 'x'])->assertForbidden();
        $this->actingAs(User::query()->create(['name' => 'Owner', 'email' => 'owner@jflsolar.test', 'password' => 'a long test password', 'role' => 'owner', 'active' => true, 'must_change_password' => false]), 'web');
        $this->get('/admin/subscribers')->assertSee('Save and test')->assertSee('(from the settings file)');
        $this->post('/admin/subscribers/mail-password', ['password' => 'New#Mailbox$Pass 1'])->assertRedirect('/admin/subscribers')->assertSessionHas('status_error');
        $this->assertSame('New#Mailbox$Pass 1', \App\Support\MailPassword::get());
        $this->assertSame('New#Mailbox$Pass 1', config('mail.mailers.smtp.password'));
        $this->assertStringNotContainsString('New#Mailbox', (string) file_get_contents(\App\Support\MailPassword::file()));
        $this->get('/admin/subscribers')->assertSee('18 characters (saved on this page)')->assertDontSee('New#Mailbox');
        \App\Support\MailPassword::forget();
    }

    public function test_the_email_has_the_tour_and_an_unsubscribe_link(): void
    {
        $this->signup('ana@example.com');
        $member = Member::query()->where('email', 'ana@example.com')->first();
        $html = (new MemberWelcome($member))->render();
        $this->assertStringContainsString('Hello Juan,', $html);
        $this->assertStringContainsString('quick tour of everything you can do', $html);
        $this->assertStringContainsString('/courses/solar-pv-system-design', $html);
        $this->assertStringContainsString('we also deliver via Lalamove', $html);
        $this->assertStringContainsString('0969 471 2098', $html);
        $this->assertStringContainsString('/newsletter/unsubscribe/', $html);
        $this->assertStringNotContainsString('solar system', str_replace('solar PV system', '', $html));
    }

    public function test_staff_can_send_it_to_members_still_waiting(): void
    {
        Mail::fake();
        WelcomeEmail::ensureColumn();
        $m = Member::query()->create(['name' => 'Waiting Member', 'email' => 'wait@example.com', 'password' => 'a long test password', 'status' => 'active']);
        $this->assertSame(1, WelcomeEmail::pendingCount());

        $owner = User::query()->create(['name' => 'Owner', 'email' => 'owner@jflsolar.test', 'password' => 'a long test password', 'role' => 'owner', 'active' => true, 'must_change_password' => false]);
        $this->actingAs($owner, 'web');
        $this->get('/admin/subscribers')->assertOk()->assertSee('Automatic welcome email')->assertSee('Send it to 1 waiting member');
        $this->post('/admin/subscribers/welcome')->assertRedirect('/admin/subscribers');
        Mail::assertSent(MemberWelcome::class, fn ($x) => $x->hasTo('wait@example.com'));
        $this->assertSame(0, WelcomeEmail::pendingCount());
        $this->get('/admin/subscribers')->assertDontSee('waiting member');
    }
}
