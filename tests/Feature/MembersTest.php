<?php

namespace Tests\Feature;

use App\Mail\MemberPasswordReset;
use App\Models\Member;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Support\Newsletter;
use App\Support\PublicContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MembersTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'sunny days on the roof';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        config(['shop.enabled' => true, 'mail.default' => 'array']);
        PublicContent::flush();
        Http::fake();
    }

    private function signupForm(array $changes = []): array
    {
        return array_merge(['name' => 'Maria Santos', 'email' => 'Maria@Example.com', 'phone' => '0917 555 1234', 'password' => self::PASSWORD, 'password_confirmation' => self::PASSWORD, 'agree' => '1'], $changes);
    }

    private function member(array $attrs = []): Member
    {
        return Member::query()->create(array_merge(['name' => 'Maria Santos', 'email' => 'maria@example.com', 'phone' => '0917 555 1234', 'password' => self::PASSWORD, 'status' => 'active'], $attrs));
    }

    private function staff(): User
    {
        return User::query()->create(['name' => 'Editor', 'email' => 'editor@jflsolar.test', 'password' => 'a long test password', 'role' => 'editor', 'active' => true, 'must_change_password' => false]);
    }

    public function test_sign_up_creates_an_account_signs_in_and_can_join_the_newsletter(): void
    {
        $this->get('/signup')->assertOk()->assertSee('Create your account')->assertSee('noindex', false);
        $this->post('/signup', $this->signupForm(['newsletter' => '1']))->assertRedirect('/account');

        $m = Member::query()->sole();
        $this->assertSame('maria@example.com', $m->email);   // stored in lower case
        $this->assertNotSame(self::PASSWORD, $m->password);   // hashed
        $this->assertAuthenticatedAs($m, 'member');
        $this->assertGuest('web');   // never a staff sign-in
        $this->assertTrue(Newsletter::isSubscribed('maria@example.com'));
        $this->get('/account')->assertOk()->assertSee('Hello, Maria Santos')->assertSee('No orders yet');
        $this->get('/')->assertSee('Your account');
    }

    public function test_sign_up_rejects_bad_input_duplicates_and_bots(): void
    {
        $this->post('/signup', $this->signupForm(['password' => 'short', 'password_confirmation' => 'short']))->assertSessionHasErrors('password');
        $this->post('/signup', $this->signupForm(['agree' => '']))->assertSessionHasErrors('agree');
        $this->post('/signup', $this->signupForm(['email' => 'not-an-email']))->assertSessionHasErrors('email');
        $this->post('/signup', $this->signupForm(['website' => 'spam.example']))->assertRedirect('/');
        $this->assertSame(0, Member::query()->count());

        $this->member();
        $this->post('/signup', $this->signupForm())->assertSessionHasErrors('email');
        $this->assertSame(1, Member::query()->count());
        $this->assertFalse(Newsletter::isSubscribed('maria@example.com'));   // not without the box ticked
    }

    public function test_sign_in_sign_out_and_disabled_accounts(): void
    {
        $m = $this->member();
        $this->post('/login', ['email' => 'maria@example.com', 'password' => 'wrong password'])->assertRedirect('/login')->assertSessionHas('account_error');
        $this->assertGuest('member');

        $this->post('/login', ['email' => 'MARIA@example.com', 'password' => self::PASSWORD])->assertRedirect('/account');
        $this->assertAuthenticatedAs($m, 'member');
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest('member');

        // Disabled by staff: signed out on the next page, and cannot sign in.
        $this->post('/login', ['email' => 'maria@example.com', 'password' => self::PASSWORD]);
        $m->forceFill(['status' => 'disabled'])->save();
        $this->app['auth']->forgetGuards();   // a real next request loads the account afresh
        $this->get('/account')->assertRedirect('/login?next=%2Faccount');
        $this->post('/login', ['email' => 'maria@example.com', 'password' => self::PASSWORD])->assertSessionHas('account_error');
        $this->assertGuest('member');
    }

    public function test_repeated_wrong_passwords_are_slowed_down(): void
    {
        $this->member();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'maria@example.com', 'password' => 'wrong password '.$i]);
        }
        $this->post('/login', ['email' => 'maria@example.com', 'password' => self::PASSWORD])->assertSessionHas('account_error', fn ($m) => str_contains($m, 'Too many attempts'));
        $this->assertGuest('member');
    }

    public function test_after_sign_in_only_pages_on_this_site_are_followed(): void
    {
        $this->member();
        $this->post('/login', ['email' => 'maria@example.com', 'password' => self::PASSWORD, 'next' => '/checkout'])->assertRedirect('/checkout');
        $this->post('/logout');
        foreach (['https://evil.example', '//evil.example', '/\\evil.example', '/admin/staff'] as $bad) {
            $this->post('/login', ['email' => 'maria@example.com', 'password' => self::PASSWORD, 'next' => $bad])->assertRedirect('/account');
            $this->post('/logout');
        }
    }

    public function test_members_never_reach_the_admin_and_staff_stay_signed_in_alongside(): void
    {
        $this->member();
        $this->post('/login', ['email' => 'maria@example.com', 'password' => self::PASSWORD]);
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/members')->assertRedirect('/admin/login');

        // Same browser, staff signs in too; signing the member out keeps the staff session.
        $staff = $this->staff();
        $this->actingAs($staff, 'web');
        $this->post('/logout');
        $this->assertGuest('member');
        $this->assertAuthenticatedAs($staff, 'web');
        $this->get('/admin')->assertOk();
    }

    public function test_account_page_needs_sign_in_and_saves_details(): void
    {
        $this->get('/account')->assertRedirect('/login?next=%2Faccount');
        $m = $this->member();
        $this->actingAs($m, 'member');
        $this->post('/account', ['name' => 'Maria S. Santos', 'phone' => '0918 000 1111', 'newsletter' => '1'])->assertRedirect('/account#details');
        $this->assertSame('Maria S. Santos', $m->fresh()->name);
        $this->assertTrue(Newsletter::isSubscribed($m->email));
        $this->post('/account', ['name' => 'Maria S. Santos', 'phone' => '']);
        $this->assertFalse(Newsletter::isSubscribed($m->email));
        $this->assertNull($m->fresh()->phone);

        $this->post('/account/password', ['current_password' => 'nope', 'password' => 'another long password', 'password_confirmation' => 'another long password'])->assertSessionHasErrors('current_password');
        $this->post('/account/password', ['current_password' => self::PASSWORD, 'password' => 'another long password', 'password_confirmation' => 'another long password'])->assertSessionHas('account_status');
        $this->post('/logout');
        $this->post('/login', ['email' => 'maria@example.com', 'password' => 'another long password'])->assertRedirect('/account');
    }

    public function test_checkout_fills_in_member_details_and_their_orders_show_on_the_account_page(): void
    {
        $category = ProductCategory::query()->where('status', 'published')->firstOrFail();
        $p = Product::query()->create(['slug' => 'test-breaker', 'name' => 'Breaker DC 2P 25A', 'brand' => 'Leikeking', 'model' => 'DC MCB 25A', 'category_id' => $category->id, 'summary' => '', 'description' => 'x', 'specs' => [], 'pricing_mode' => 'fixed', 'price_php' => 480, 'price_basis' => 'Pickup price per piece', 'price_is_sample' => false, 'availability' => 'available', 'status' => 'published', 'is_sample' => false]);
        PublicContent::flush();

        // Guests are invited to sign in, but can still check out.
        $this->post('/cart/add', ['product' => $p->id, 'quantity' => 1]);
        $this->get('/checkout')->assertSee('Have an account?')->assertSee('/login?next=%2Fcheckout', false);

        $m = $this->member();
        $this->actingAs($m, 'member');
        $page = $this->get('/checkout')->assertSee('Signed in as')->assertSee('value="Maria Santos"', false)->assertSee('value="maria@example.com"', false)->assertSee('value="0917 555 1234"', false);
        preg_match('/name="fingerprint" value="([a-f0-9]{64})"/', $page->getContent(), $fp);
        $this->post('/checkout', ['name' => 'Maria Santos', 'phone' => '0917 555 1234', 'email' => 'maria@example.com', 'notes' => '', 'agree' => '1', 'payment' => 'cash', 'pickup' => 'warehouse', 'fingerprint' => $fp[1]]);

        $order = Order::query()->sole();
        $this->assertSame($m->id, $order->member_id);
        $this->get('/account')->assertSee($order->reference)->assertSee('₱480');

        // Another member never sees it.
        $other = $this->member(['email' => 'other@example.com']);
        $this->actingAs($other, 'member');
        $this->get('/account')->assertDontSee($order->reference);
    }

    public function test_forgot_and_reset_password(): void
    {
        Mail::fake();
        $this->member();
        $this->post('/forgot-password', ['email' => 'nobody@example.com'])->assertSessionHas('account_status');   // same answer
        Mail::assertNothingSent();
        $this->post('/forgot-password', ['email' => 'maria@example.com'])->assertSessionHas('account_status');
        $url = null;
        Mail::assertSent(MemberPasswordReset::class, function (MemberPasswordReset $mail) use (&$url) {
            $url = $mail->url;

            return $mail->hasTo('maria@example.com');
        });
        preg_match('#/reset-password/([A-Za-z0-9]+)\?email=#', $url, $m);
        $this->get('/reset-password/'.$m[1].'?email=maria%40example.com')->assertOk()->assertSee('Choose a new password');

        $this->post('/reset-password', ['token' => 'wrongtoken1234567890abc', 'email' => 'maria@example.com', 'password' => 'brand new passphrase', 'password_confirmation' => 'brand new passphrase'])->assertSessionHasErrors('password');
        $this->post('/reset-password', ['token' => $m[1], 'email' => 'maria@example.com', 'password' => 'brand new passphrase', 'password_confirmation' => 'brand new passphrase'])->assertRedirect('/login');
        $this->post('/login', ['email' => 'maria@example.com', 'password' => 'brand new passphrase'])->assertRedirect('/account');
    }

    public function test_footer_newsletter_needs_consent_and_unsubscribe_is_a_button_not_the_link(): void
    {
        $this->get('/')->assertSee('Get our promos and solar news')->assertSee('action="/newsletter"', false);
        $this->from('/products')->post('/newsletter', ['email' => 'juan@example.com'])->assertSessionHas('newsletter_error');
        $this->assertFalse(Newsletter::isSubscribed('juan@example.com'));
        $this->from('/products')->post('/newsletter', ['email' => 'Juan@Example.com', 'consent' => '1'])->assertRedirect('/products#newsletter')->assertSessionHas('newsletter_status');
        $this->assertTrue(Newsletter::isSubscribed('juan@example.com'));

        $token = DB::table('newsletter_subscribers')->where('email', 'juan@example.com')->value('token');
        $this->get('/newsletter/unsubscribe/'.$token)->assertOk()->assertSee('Unsubscribe from our emails?');
        $this->assertTrue(Newsletter::isSubscribed('juan@example.com'));   // opening the link alone does nothing
        $this->post('/newsletter/unsubscribe/'.$token)->assertSee('You’re unsubscribed', false);
        $this->assertFalse(Newsletter::isSubscribed('juan@example.com'));
        $this->post('/newsletter/unsubscribe/'.str_repeat('a', 40))->assertSee('Link not recognised');

        // Signing up again later works.
        $this->from('/')->post('/newsletter', ['email' => 'juan@example.com', 'consent' => '1']);
        $this->assertTrue(Newsletter::isSubscribed('juan@example.com'));
    }

    public function test_staff_manage_members_and_download_subscribers(): void
    {
        $m = $this->member();
        Newsletter::subscribe('=HYPERLINK("http://x")@example.com', 'Eve', 'footer');
        Newsletter::subscribe('juan@example.com', 'Juan', 'footer');
        $staff = $this->staff();

        $this->actingAs($staff)->get('/admin/members')->assertOk()->assertSee('maria@example.com');
        $this->actingAs($staff)->get('/admin/members?q=nobody')->assertSee('No members match');
        $this->actingAs($staff)->post('/admin/members/'.$m->id, ['action' => 'disable'])->assertRedirect('/admin/members');
        $this->assertSame('disabled', $m->fresh()->status);
        $this->actingAs($staff)->post('/admin/members/'.$m->id, ['action' => 'enable']);
        $this->assertSame('active', $m->fresh()->status);

        $this->actingAs($staff)->get('/admin/subscribers')->assertOk()->assertSee('juan@example.com');
        $csv = $this->actingAs($staff)->get('/admin/subscribers/export')->assertOk()->streamedContent();
        $this->assertStringContainsString('juan@example.com,Juan', $csv);
        $this->assertStringContainsString("'=hyperlink", $csv);   // formulas neutralised for Excel
        $this->assertStringContainsString('/newsletter/unsubscribe/', $csv);

        // Members and the public cannot download it.
        auth('web')->logout();
        $this->actingAs($m, 'member')->get('/admin/subscribers/export')->assertRedirect('/admin/login');
    }

    public function test_account_pages_are_kept_out_of_search_engines(): void
    {
        config(['site.env' => 'production']);
        $robots = $this->get('/robots.txt')->getContent();
        foreach (['/account', '/login', '/signup', '/reset-password/'] as $path) {
            $this->assertStringContainsString('Disallow: '.$path, $robots);
        }
        $this->get('/login')->assertSee('noindex', false);
    }
}
