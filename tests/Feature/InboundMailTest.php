<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Support\InboundMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InboundMailTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = '4a6f8b2d1e9c3f7a0b5d8e2c4f1a7b3d9e6c2f8a1b4d7e0c3f6a9b2d5e8c1f4';

    private function hook(array $payload, ?string $secret = self::SECRET)
    {
        return $this->call('POST', '/webhooks/hostinger-mail', [], [], [], array_filter(['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => $secret ? 'Bearer '.$secret : null]), json_encode($payload));
    }

    public function test_email_to_info_becomes_an_inquiry_once(): void
    {
        InboundMail::setSecret(self::SECRET);
        $payload = ['event' => 'message.received', 'mailbox' => 'info@jflsolar.ph',
            'message' => ['from' => 'Juan Dela Cruz <Juan@Example.com>', 'subject' => 'Quote for 10 kW hybrid', 'text' => 'Hi, how much is a 10 kW hybrid system?', 'thread_id' => 'thr_1']];
        $this->hook($payload)->assertOk()->assertJson(['ok' => true]);
        $i = Inquiry::query()->sole();
        $this->assertSame(['Juan Dela Cruz', 'juan@example.com', 'email', 'new'], [$i->name, $i->email, $i->inquiry_type, $i->status]);
        $this->assertStringContainsString("Subject: Quote for 10 kW hybrid\n\nHi, how much", $i->message);

        $this->hook($payload)->assertOk();                                   // repeat delivery
        $this->hook(['message' => ['from' => ['address' => 'noreply@jflsolar.ph'], 'subject' => 'x']])->assertOk();   // own domain
        $this->hook(['event' => 'test'])->assertOk();                        // Hostinger test, no sender
        $this->assertSame(1, Inquiry::query()->count());
    }

    public function test_wrong_or_missing_secret_is_refused(): void
    {
        $this->hook(['message' => ['from' => 'a@b.com']])->assertStatus(401);   // not linked yet
        InboundMail::setSecret(self::SECRET);
        $this->hook(['message' => ['from' => 'a@b.com']], 'wrong-secret-value-123456')->assertStatus(401);
        $this->hook(['message' => ['from' => 'a@b.com']], null)->assertStatus(401);
        $this->assertSame(0, Inquiry::query()->count());
    }

    public function test_staff_link_the_mailbox_and_see_email_inquiries(): void
    {
        $this->seed();
        $staff = \App\Models\User::query()->create(['name' => 'Admin', 'email' => 'admin2@jflsolar.test', 'password' => 'a long test password', 'role' => 'admin', 'active' => true, 'must_change_password' => false]);
        $this->actingAs($staff)->get('/admin/inquiries')->assertOk()->assertSee('not linked yet')->assertSee('/webhooks/hostinger-mail');
        $this->actingAs($staff)->post('/admin/inquiries/email-link', ['secret' => self::SECRET])->assertRedirect('/admin/inquiries');
        $this->assertTrue(InboundMail::configured());
        InboundMail::store(['message' => ['from' => 'maria@example.com', 'subject' => 'Battery', 'text' => 'Hello']]);
        $this->actingAs($staff)->get('/admin/inquiries')->assertSee('linked')->assertSee('Email to info@jflsolar.ph')->assertSee('maria@example.com');
        $this->actingAs($staff)->post('/admin/inquiries/email-link', ['clear' => 1]);
        $this->assertFalse(InboundMail::configured());
    }
}
