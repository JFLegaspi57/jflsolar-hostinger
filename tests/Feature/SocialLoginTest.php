<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use App\Support\Members;
use App\Support\PublicContent;
use App\Support\SocialLogin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SocialLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        PublicContent::flush();
        Cache::forget('social-accounts-table-v1');
        Members::flushState();
        Members::ensureTables();
        @unlink(SocialLogin::file());
    }

    protected function tearDown(): void
    {
        @unlink(SocialLogin::file());
        parent::tearDown();
    }

    private function owner(): User
    {
        return User::query()->create(['name' => 'Owner', 'email' => 'owner@jflsolar.test', 'password' => 'a long test password', 'role' => 'owner', 'active' => true, 'must_change_password' => false]);
    }

    /** Starts the flow and returns the state the provider would send back. */
    private function start(string $provider, array $extra = []): string
    {
        $r = $this->get('/auth/start?'.http_build_query(['provider' => $provider] + $extra));
        $r->assertRedirect();
        $this->assertStringStartsWith($provider === 'google' ? 'https://accounts.google.com/' : 'https://www.facebook.com/', $r->headers->get('Location'));
        parse_str((string) parse_url($r->headers->get('Location'), PHP_URL_QUERY), $q);
        $this->assertStringEndsWith('/auth/'.$provider.'/callback', $q['redirect_uri']);

        return $q['state'];
    }

    private function fakeGoogle(string $email, bool $verified = true, string $sub = 'g-123'): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory);   // forget earlier fake replies
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'g-token']),
            'openidconnect.googleapis.com/*' => Http::response(['sub' => $sub, 'email' => $email, 'email_verified' => $verified, 'name' => 'Juan Dela Cruz']),
        ]);
    }

    public function test_buttons_show_only_when_the_owner_has_saved_the_keys(): void
    {
        $this->get('/signup')->assertOk()->assertDontSee('Sign up with Google');
        $this->get('/auth/start?provider=google')->assertRedirect('/login');

        $editor = User::query()->create(['name' => 'Editor', 'email' => 'editor@jflsolar.test', 'password' => 'a long test password', 'role' => 'editor', 'active' => true, 'must_change_password' => false]);
        $this->actingAs($editor, 'web')->get('/admin/social-login')->assertForbidden();

        $this->actingAs($this->owner(), 'web');
        $this->get('/admin/social-login')->assertOk()->assertSee('/auth/google/callback')->assertSee('/auth/facebook/callback');
        $this->post('/admin/social-login', ['provider' => 'google', 'app_id' => '123-abc.apps.googleusercontent.com', 'app_secret' => 'GOCSPX-secret'])->assertRedirect('/admin/social-login');
        $this->assertTrue(SocialLogin::enabled('google'));
        $this->assertFalse(SocialLogin::enabled('facebook'));
        $this->assertStringNotContainsString('GOCSPX-secret', (string) file_get_contents(SocialLogin::file()));
        $this->get('/admin/social-login')->assertSee('Google: ON')->assertDontSee('GOCSPX-secret');
        // A blank secret keeps the saved one.
        $this->post('/admin/social-login', ['provider' => 'google', 'app_id' => '123-abc.apps.googleusercontent.com', 'app_secret' => '']);
        $this->assertSame('GOCSPX-secret', SocialLogin::keys('google')[1]);

        $this->get('/signup')->assertSee('Sign up with Google')->assertDontSee('Sign up with Facebook');
        $this->get('/login')->assertSee('Continue with Google');
    }

    public function test_google_creates_the_account_then_signs_it_in_again(): void
    {
        SocialLogin::save('google', 'gid', 'gsecret');
        $state = $this->start('google', ['newsletter' => '1', 'next' => '/courses/solar-pv-system-design']);
        $this->fakeGoogle('Juan@Example.com');
        $this->get('/auth/google/callback?'.http_build_query(['state' => $state, 'code' => 'abc']))->assertRedirect('/courses/solar-pv-system-design');

        $m = Member::query()->where('email', 'juan@example.com')->first();
        $this->assertNotNull($m);
        $this->assertSame('Juan Dela Cruz', $m->name);
        $this->assertSame($m->id, Members::current()?->id);
        $this->assertSame(['google'], SocialLogin::linked($m->id));
        $this->assertTrue(DB::table('newsletter_subscribers')->where('email', 'juan@example.com')->exists());
        Http::assertSent(fn ($r) => str_contains($r->url(), 'oauth2.googleapis.com/token') && $r['code'] === 'abc' && $r['client_secret'] === 'gsecret');

        // Later: same Google account, same member (even if the email changed at Google).
        $this->post('/logout');
        $state = $this->start('google');
        $this->fakeGoogle('juan.new@example.com');
        $this->get('/auth/google/callback?'.http_build_query(['state' => $state, 'code' => 'def']))->assertRedirect('/account');
        $this->assertSame($m->id, Members::current()?->id);
        $this->assertSame(0, Member::query()->where('email', 'juan.new@example.com')->count());
    }

    public function test_google_links_an_existing_account_but_facebook_does_not(): void
    {
        SocialLogin::save('google', 'gid', 'gsecret');
        SocialLogin::save('facebook', 'fid', 'fsecret');
        $m = Member::query()->create(['name' => 'Maria', 'email' => 'maria@example.com', 'password' => 'a long test password', 'status' => 'active']);

        $state = $this->start('facebook');
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake([
            'graph.facebook.com/*/oauth/access_token*' => Http::response(['access_token' => 'f-token']),
            'graph.facebook.com/*/me*' => Http::response(['id' => 'f-9', 'name' => 'Maria FB', 'email' => 'maria@example.com']),
        ]);
        $this->get('/auth/facebook/callback?'.http_build_query(['state' => $state, 'code' => 'x']))->assertRedirect('/login')->assertSessionHas('account_error');
        $this->assertNull(Members::current());

        $state = $this->start('google');
        $this->fakeGoogle('maria@example.com', true, 'g-maria');
        $this->get('/auth/google/callback?'.http_build_query(['state' => $state, 'code' => 'y']))->assertRedirect('/account');
        $this->assertSame($m->id, Members::current()?->id);
        $this->assertSame(1, Member::query()->where('email', 'maria@example.com')->count());

        // An unverified Google email is never matched or used.
        $this->post('/logout');
        $state = $this->start('google');
        $this->fakeGoogle('maria@example.com', false, 'g-other');
        $this->get('/auth/google/callback?'.http_build_query(['state' => $state, 'code' => 'z']))->assertRedirect('/login')->assertSessionHas('account_error');
    }

    public function test_facebook_creates_a_new_account(): void
    {
        SocialLogin::save('facebook', 'fid', 'fsecret');
        $state = $this->start('facebook');
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake([
            'graph.facebook.com/*/oauth/access_token*' => Http::response(['access_token' => 'f-token']),
            'graph.facebook.com/*/me*' => Http::response(['id' => 'f-1', 'name' => 'Pedro Penduko', 'email' => 'pedro@example.com']),
        ]);
        $this->get('/auth/facebook/callback?'.http_build_query(['state' => $state, 'code' => 'c']))->assertRedirect('/account')->assertSessionHas('account_status');
        $this->assertSame('Pedro Penduko', Members::current()?->name);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/me') && str_contains($r->url(), 'appsecret_proof='));
    }

    public function test_a_wrong_state_cancel_or_disabled_account_is_refused(): void
    {
        SocialLogin::save('google', 'gid', 'gsecret');
        $this->start('google');
        $this->get('/auth/google/callback?state=wrong&code=a')->assertRedirect('/login')->assertSessionHas('account_error');

        $state = $this->start('google');
        $this->get('/auth/google/callback?'.http_build_query(['state' => $state, 'error' => 'access_denied']))->assertRedirect('/login')->assertSessionHas('account_error');

        Member::query()->create(['name' => 'Off', 'email' => 'off@example.com', 'password' => 'a long test password', 'status' => 'disabled']);
        $state = $this->start('google');
        $this->fakeGoogle('off@example.com', true, 'g-off');
        $this->get('/auth/google/callback?'.http_build_query(['state' => $state, 'code' => 'b']))->assertRedirect('/login')->assertSessionHas('account_error');
        $this->assertNull(Members::current());
    }
}
