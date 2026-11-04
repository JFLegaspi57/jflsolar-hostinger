<?php

namespace Tests\Feature;

use App\Mail\NewInquiry;
use App\Mail\SmallSystemReply;
use App\Models\Inquiry;
use App\Support\Inquiries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SmallSystemReplyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        RateLimiter::clear(Inquiries::clientKey('127.0.0.1'));
    }

    private function send(string $type, string $bill, string $email = 'ana@example.com')
    {
        return $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class)->post('/contact', [
            'token' => Inquiries::issueToken(time() - 30),
            'name' => 'Ana Cruz', 'phone' => '09171234567', 'email' => $email, 'location' => 'Imus, Cavite',
            'inquiry_type' => $type, 'bill_range' => $bill, 'message' => 'Please quote a solar system for our home.',
        ]);
    }

    public function test_bills_below_5000_get_the_diy_reply_on_screen_and_by_email(): void
    {
        config(['mail.default' => 'array', 'site.inquiry.notify' => '']);
        Mail::fake();

        $this->send('residential', '3k-5k')->assertRedirect('/contact#quote')->assertSessionHas('inquiry_small', true);
        $this->assertSame(1, Inquiry::query()->count());
        Mail::assertSent(SmallSystemReply::class, fn ($m) => $m->hasTo('ana@example.com'));
        Mail::assertNotSent(NewInquiry::class);

        $this->withSession(['inquiry_sent' => true, 'inquiry_small' => true])->get('/contact')->assertOk()
            ->assertSee('Options for a smaller system')->assertSee('href="/packages/build-your-own"', false);
        $mail = (new SmallSystemReply(Inquiry::query()->first()))->render();
        $this->assertStringContainsString('smaller than 6 kW', $mail);
        $this->assertStringContainsString('/packages?type=materials', $mail);
    }

    public function test_larger_bills_and_other_inquiry_types_get_the_normal_reply(): void
    {
        config(['mail.default' => 'array']);
        Mail::fake();

        $this->send('residential', '10k-20k')->assertSessionHas('inquiry_small', false);
        $this->send('support', 'under-3k')->assertSessionHas('inquiry_small', false);   // bill not asked for support
        Mail::assertNotSent(SmallSystemReply::class);
        $this->withSession(['inquiry_sent' => true])->get('/contact')->assertOk()->assertDontSee('Options for a smaller system');
        $this->assertFalse(Inquiries::isSmallSystem('residential', '5k-10k'));
        $this->assertTrue(Inquiries::isSmallSystem('package', 'under-3k'));
    }

    public function test_no_email_until_mail_is_set_up(): void
    {
        config(['mail.default' => 'log']);
        Mail::fake();
        $this->send('residential', 'under-3k')->assertSessionHas('inquiry_small', true);
        Mail::assertNotSent(SmallSystemReply::class);
    }
}
