<?php

namespace Tests\Feature;

use App\Mail\MemberEmailChange;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MemberEmailChangeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Cache::forget('member-email-changes-table-v1');
        Cache::forget('member-messages-table-v1');
    }

    private function member(string $email = 'typo@gmail.con'): Member
    {
        return Member::query()->create(['name' => 'Coleen', 'email' => $email, 'password' => 'a long test password', 'status' => 'active']);
    }

    public function test_the_new_email_applies_only_after_the_confirmation_link(): void
    {
        Mail::fake();
        $m = $this->member();
        \App\Support\Newsletter::subscribe($m->email, $m->name, 'account');

        $this->actingAs($m, 'member')->get('/account')->assertSee('Change email');
        $this->post('/account/email', ['email' => 'Coleen.Real@Gmail.com'])->assertRedirect('/account#email');
        $this->assertSame('typo@gmail.con', $m->fresh()->email);
        $this->get('/account')->assertSee('Waiting for you to confirm')->assertSee('coleen.real@gmail.com');

        $url = null;
        Mail::assertSent(MemberEmailChange::class, function (MemberEmailChange $mail) use (&$url) {
            $url = $mail->url;

            return $mail->hasTo('coleen.real@gmail.com');
        });
        $path = parse_url($url, PHP_URL_PATH);
        $this->get($path)->assertRedirect('/account#email');
        $this->assertSame('coleen.real@gmail.com', $m->fresh()->email);
        $this->assertTrue(\App\Support\Newsletter::isSubscribed('coleen.real@gmail.com'));
        $this->assertFalse(\App\Support\Newsletter::isSubscribed('typo@gmail.con'));

        $this->get($path)->assertSessionHas('account_error');   // a link works once
    }

    public function test_invalid_taken_or_same_addresses_are_refused(): void
    {
        Mail::fake();
        $m = $this->member();
        $this->member('taken@gmail.com');

        $this->actingAs($m, 'member')->post('/account/email', ['email' => 'not an email'])->assertSessionHasErrors('email');
        $this->post('/account/email', ['email' => 'taken@gmail.com'])->assertSessionHasErrors('email');
        $this->post('/account/email', ['email' => 'TYPO@gmail.con'])->assertSessionHasErrors('email');
        Mail::assertNothingSent();
        $this->get('/account/email/confirm/'.str_repeat('a', 48))->assertSessionHas('account_error');
    }

    public function test_an_unread_message_shows_on_every_page_until_closed(): void
    {
        $m = $this->member();
        \App\Support\MemberMessages::add($m->id, 'Please update your email', 'Hi');

        $this->actingAs($m, 'member')->get('/products')->assertSee('You have a message from JFL Solar');
        $this->get('/account')->assertDontSee('You have a message from JFL Solar')->assertSee('Please update your email');
        $id = \App\Support\MemberMessages::open($m->id)->first()->id;
        $this->post('/account/messages/'.$id.'/close');
        $this->get('/products')->assertDontSee('You have a message from JFL Solar');
    }

    public function test_guests_see_no_message_strip(): void
    {
        $this->get('/products')->assertOk()->assertDontSee('You have a message from JFL Solar');
    }
}
