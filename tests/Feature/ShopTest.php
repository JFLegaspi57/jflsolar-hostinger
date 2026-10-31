<?php

namespace Tests\Feature;

use App\Mail\NewOrder;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Support\PayMongo;
use App\Support\PublicContent;
use App\Support\Shop;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'whsk_test_secret';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        config([
            'shop.enabled' => true,
            'shop.paymongo.secret_key' => 'sk_test_abc',
            'shop.paymongo.webhook_secret' => self::SECRET,
            'shop.notify' => 'owner@jflsolar.test',
            'mail.default' => 'array',
        ]);
        PublicContent::flush();
    }

    private function product(array $attrs = []): Product
    {
        $category = ProductCategory::query()->where('status', 'published')->firstOrFail();
        $p = Product::query()->create(array_merge([
            'slug' => 'test-breaker-'.uniqid(), 'name' => 'Breaker DC 2P 25A', 'brand' => 'Leikeking', 'model' => 'DC MCB 25A',
            'category_id' => $category->id, 'summary' => '', 'description' => 'x', 'specs' => [],
            'pricing_mode' => 'fixed', 'price_php' => 480, 'price_basis' => 'Pickup price per piece', 'price_is_sample' => false,
            'availability' => 'available', 'status' => 'published', 'is_sample' => false,
        ], $attrs));
        PublicContent::flush();

        return $p;
    }

    private function fakePayMongo(): void
    {
        Http::fake(['api.paymongo.com/*' => Http::response(['data' => ['id' => 'cs_test_123', 'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/cs_test_123']]], 200)]);
    }

    private function checkoutForm(array $changes = []): array
    {
        preg_match('/name="fingerprint" value="([a-f0-9]{64})"/', $this->get('/checkout')->getContent(), $m);

        return array_merge(['name' => 'Juan Dela Cruz', 'phone' => '0917 123 4567', 'email' => 'juan@example.com', 'notes' => '', 'agree' => '1', 'payment' => 'online', 'pickup' => 'warehouse', 'fingerprint' => $m[1] ?? ''], $changes);
    }

    /** Places an order for 2 × ₱480 and returns it (status pending_payment). */
    private function placeOrder(): Order
    {
        $p = $this->product();
        $this->fakePayMongo();
        $this->post('/cart/add', ['product' => $p->id, 'quantity' => 2])->assertRedirect('/products/'.$p->slug);
        $this->post('/checkout', $this->checkoutForm())->assertRedirect('https://checkout.paymongo.com/cs_test_123');

        return Order::query()->with('items')->latest('id')->firstOrFail();
    }

    private function webhook(array $payload, ?string $secret = null, ?int $t = null): \Illuminate\Testing\TestResponse
    {
        $raw = json_encode($payload);
        $t ??= time();
        $sig = hash_hmac('sha256', $t.'.'.$raw, $secret ?? self::SECRET);

        return $this->call('POST', '/webhooks/paymongo', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_PAYMONGO_SIGNATURE' => "t={$t},te={$sig},li="], $raw);
    }

    private function paidEvent(Order $order, int $centavos, string $session = 'cs_test_123'): array
    {
        return ['data' => ['id' => 'evt_1', 'type' => 'event', 'attributes' => [
            'type' => 'checkout_session.payment.paid', 'livemode' => false,
            'data' => ['id' => $session, 'type' => 'checkout_session', 'attributes' => [
                'reference_number' => $order->reference,
                'payments' => [['id' => 'pay_1', 'attributes' => ['amount' => $centavos, 'status' => 'paid', 'source' => ['type' => 'gcash']]]],
            ]],
        ]]];
    }

    public function test_shop_is_hidden_until_switched_on_and_configured(): void
    {
        $p = $this->product();
        config(['shop.enabled' => false]);
        $this->get('/cart')->assertNotFound();
        $this->get('/products/'.$p->slug)->assertOk()->assertDontSee('Add to cart')->assertSee('Request a Product Quote');

        // Switched on but no way to pay (no PayMongo, pay at pickup off): still hidden.
        config(['shop.enabled' => true, 'shop.paymongo.secret_key' => '', 'shop.pay_in_person' => false]);
        $this->get('/cart')->assertNotFound();

        config(['shop.paymongo.secret_key' => 'sk_test_abc']);
        $this->get('/products/'.$p->slug)->assertSee('Add to cart');
    }

    public function test_only_fixed_price_available_real_products_can_be_bought(): void
    {
        $quote = $this->product(['pricing_mode' => 'quote', 'price_php' => null]);
        $sample = $this->product(['is_sample' => true]);
        $ask = $this->product(['availability' => 'on_request']);
        $draft = $this->product(['status' => 'draft']);
        foreach ([$quote, $sample, $ask, $draft] as $p) {
            $this->post('/cart/add', ['product' => $p->id, 'quantity' => 1]);
        }
        $this->get('/cart')->assertSee('Your cart is empty');
        $this->get('/products/'.$quote->slug)->assertDontSee('Add to cart');
    }

    public function test_cart_prices_come_from_the_catalog_and_follow_changes(): void
    {
        $p = $this->product();
        $this->post('/cart/add', ['product' => $p->id, 'quantity' => 3, 'price' => 1]);
        $this->get('/cart')->assertSee('₱1,440');

        $p->update(['price_php' => 500]);
        PublicContent::flush();
        $this->get('/cart')->assertSee('₱1,500');

        $p->update(['availability' => 'unavailable']);
        PublicContent::flush();
        $this->get('/cart')->assertSee('were removed')->assertSee('Your cart is empty');
    }

    public function test_per_metre_products_are_sold_in_metres(): void
    {
        $p = $this->product(['name' => 'Flexible Conduit 25mm 100M', 'price_php' => 85, 'price_basis' => 'Per meter, pickup price']);
        $this->get('/products/'.$p->slug)->assertSee('Quantity (metres)');
        $this->post('/cart/add', ['product' => $p->id, 'quantity' => 30]);
        $this->get('/checkout')->assertSee('30 metres')->assertSee('₱2,550');
    }

    public function test_quantities_are_clamped_and_can_be_updated_or_removed(): void
    {
        $p = $this->product();
        $this->post('/cart/add', ['product' => $p->id, 'quantity' => 99999]);
        $this->assertSame(500, session('cart')[$p->id]);
        $this->post('/cart', ['quantity' => [$p->id => 4]]);
        $this->assertSame(4, session('cart')[$p->id]);
        $this->post('/cart', ['remove' => $p->id]);
        $this->assertSame([], session('cart'));
    }

    public function test_checkout_validates_details(): void
    {
        $p = $this->product();
        $this->post('/cart/add', ['product' => $p->id, 'quantity' => 1]);
        $this->post('/checkout', $this->checkoutForm(['email' => 'nope', 'agree' => '']))
            ->assertRedirect('/checkout')->assertSessionHasErrors(['email', 'agree']);
        $this->assertSame(0, Order::query()->count());
    }

    public function test_checkout_creates_an_order_and_sends_the_customer_to_paymongo(): void
    {
        $order = $this->placeOrder();
        $this->assertSame('pending_payment', $order->status);
        $this->assertSame(960.0, $order->total_php);
        $this->assertSame('cs_test_123', $order->paymongo_checkout_id);
        $this->assertCount(1, $order->items);

        Http::assertSent(function ($request) use ($order) {
            if ($request->method() !== 'POST' || ! str_contains($request->url(), 'api.paymongo.com')) {
                return false;
            }
            $a = $request['data']['attributes'];

            return $request->hasHeader('Authorization', 'Basic '.base64_encode('sk_test_abc:'))
                && $a['reference_number'] === $order->reference
                && $a['line_items'][0]['amount'] === 48000 && $a['line_items'][0]['quantity'] === 2
                && str_contains($a['success_url'], $order->access_token);
        });
    }

    public function test_a_paymongo_failure_keeps_the_cart_and_saves_no_order(): void
    {
        $p = $this->product();
        Http::fake(['api.paymongo.com/*' => Http::response(['errors' => [['detail' => 'nope']]], 400)]);
        $this->post('/cart/add', ['product' => $p->id, 'quantity' => 1]);
        $this->post('/checkout', $this->checkoutForm())->assertRedirect('/checkout')->assertSessionHas('checkout_error');
        $this->assertSame(0, Order::query()->count());
        $this->assertArrayHasKey($p->id, session('cart'));
    }

    public function test_signed_webhook_marks_the_order_paid_once_and_emails_the_owner(): void
    {
        Mail::fake();
        $order = $this->placeOrder();

        $this->webhook($this->paidEvent($order, 96000))->assertOk();
        $order->refresh();
        $this->assertSame('paid', $order->status);
        $this->assertSame('gcash', $order->payment_method);
        $this->assertNotNull($order->paid_at);
        Mail::assertSent(NewOrder::class, 1);

        $this->webhook($this->paidEvent($order, 96000))->assertOk();   // PayMongo retry
        Mail::assertSent(NewOrder::class, 1);
    }

    public function test_webhook_rejects_bad_signatures_wrong_sessions_and_short_payments(): void
    {
        $order = $this->placeOrder();

        $this->webhook($this->paidEvent($order, 96000), 'wrong-secret')->assertStatus(400);
        $this->webhook($this->paidEvent($order, 96000), null, time() - 3600)->assertStatus(400);   // replayed
        $this->webhook($this->paidEvent($order, 96000, 'cs_other'))->assertOk();
        $this->assertSame('pending_payment', $order->refresh()->status);

        $this->webhook($this->paidEvent($order, 50000))->assertOk();
        $order->refresh();
        $this->assertSame('pending_payment', $order->status);
        $this->assertStringContainsString('less than the order total', $order->staff_notes);
    }

    public function test_order_page_needs_the_private_token_and_done_clears_the_cart(): void
    {
        $order = $this->placeOrder();
        $this->get('/orders/'.$order->reference.'?t=wrong-token-wrong-token-wrong-token-1234')->assertNotFound();
        $this->get('/checkout/done?order='.$order->reference.'&t='.$order->access_token)->assertRedirect($order->publicPath());
        $this->assertSame([], session('cart'));
        $this->get($order->publicPath())->assertOk()->assertSee('Confirming your payment')->assertSee('noindex', false);

        $this->webhook($this->paidEvent($order, 96000));
        $this->get($order->publicPath())->assertSee('your order is paid');
    }

    public function test_staff_manage_orders_but_cannot_mark_unpaid_orders_paid(): void
    {
        $order = $this->placeOrder();
        $staff = User::query()->create(['name' => 'Editor', 'email' => 'editor@jflsolar.test', 'password' => 'a long test password', 'role' => 'editor', 'active' => true, 'must_change_password' => false]);
        $this->get('/admin/orders')->assertRedirect('/admin/login');

        $this->actingAs($staff)->post('/admin/orders/'.$order->id, ['status' => 'ready', 'lock_version' => 0])->assertSessionHas('status_error');
        $this->assertSame('pending_payment', $order->refresh()->status);

        $this->webhook($this->paidEvent($order, 96000));
        $this->actingAs($staff)->get('/admin/orders')->assertOk()->assertSee($order->reference);
        $this->assertSame(1, $order->refresh()->lock_version);   // recording the payment bumps the version
        $this->actingAs($staff)->post('/admin/orders/'.$order->id, ['status' => 'ready', 'lock_version' => 1])->assertSessionHas('status');
        $this->assertSame('ready', $order->refresh()->status);
        // A stale form (old version) is refused instead of overwriting.
        $this->actingAs($staff)->post('/admin/orders/'.$order->id, ['status' => 'completed', 'lock_version' => 1])->assertSessionHas('status_error');
        $this->assertSame('ready', $order->refresh()->status);
    }

    public function test_payment_after_staff_cancelled_is_recorded_and_flagged(): void
    {
        Mail::fake();
        $order = $this->placeOrder();
        $staff = User::query()->create(['name' => 'Editor', 'email' => 'editor@jflsolar.test', 'password' => 'a long test password', 'role' => 'editor', 'active' => true, 'must_change_password' => false]);
        $this->actingAs($staff)->post('/admin/orders/'.$order->id, ['status' => 'cancelled', 'lock_version' => 0]);
        $this->assertSame('cancelled', $order->refresh()->status);

        $this->webhook($this->paidEvent($order, 96000))->assertOk();
        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertStringContainsString('AFTER this order was cancelled', $order->staff_notes);
        Mail::assertSent(NewOrder::class, 1);
        $this->actingAs($staff)->get('/admin/orders/'.$order->id)->assertSee('Paid after it was cancelled');
    }

    public function test_staff_cannot_cancel_an_order_that_was_paid_while_their_page_was_open(): void
    {
        $order = $this->placeOrder();
        $staff = User::query()->create(['name' => 'Editor', 'email' => 'editor@jflsolar.test', 'password' => 'a long test password', 'role' => 'editor', 'active' => true, 'must_change_password' => false]);
        $this->webhook($this->paidEvent($order, 96000));   // arrives while staff look at the unpaid order (version 0)
        $this->actingAs($staff)->post('/admin/orders/'.$order->id, ['status' => 'cancelled', 'lock_version' => 0])->assertSessionHas('status_error');
        $this->assertSame('paid', $order->refresh()->status);
    }

    public function test_underpayment_note_is_not_repeated_on_retries(): void
    {
        $order = $this->placeOrder();
        $this->webhook($this->paidEvent($order, 50000));
        $this->webhook($this->paidEvent($order, 50000));
        $this->assertSame(1, substr_count($order->refresh()->staff_notes, 'less than the order total'));
    }

    public function test_checkout_refuses_a_total_that_changed_after_the_page_was_shown(): void
    {
        $p = $this->product();
        $this->fakePayMongo();
        $this->post('/cart/add', ['product' => $p->id, 'quantity' => 1]);
        $form = $this->checkoutForm();
        $p->update(['price_php' => 999]);
        PublicContent::flush();
        $this->post('/checkout', $form)->assertRedirect('/checkout')->assertSessionHas('checkout_error');
        $this->assertSame(0, Order::query()->count());
    }

    public function test_orders_above_the_online_limit_are_sent_to_a_quote(): void
    {
        config(['shop.max_total' => 1000]);
        $p = $this->product();
        $this->post('/cart/add', ['product' => $p->id, 'quantity' => 3]);
        $this->post('/checkout', $this->checkoutForm())->assertSessionHasErrors('total');
    }

    public function test_order_page_asks_paymongo_when_the_webhook_was_missed_and_stops_refreshing(): void
    {
        $paid = false;
        $ref = null;
        Http::fake(function ($request) use (&$paid, &$ref) {
            if ($request->method() === 'POST') {
                $ref = $request['data']['attributes']['reference_number'];

                return Http::response(['data' => ['id' => 'cs_test_123', 'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/cs_test_123']]], 200);
            }

            return Http::response(['data' => ['id' => 'cs_test_123', 'attributes' => [
                'reference_number' => $ref,
                'payments' => $paid ? [['id' => 'pay_9', 'attributes' => ['amount' => 96000, 'status' => 'paid', 'source' => ['type' => 'card']]]] : [],
            ]]], 200);
        });
        $p = $this->product();
        $this->post('/cart/add', ['product' => $p->id, 'quantity' => 2]);
        $this->post('/checkout', $this->checkoutForm())->assertRedirect('https://checkout.paymongo.com/cs_test_123');
        $order = Order::query()->latest('id')->firstOrFail();

        // Not paid yet: after about two minutes of checks the page stops refreshing itself.
        $this->get($order->publicPath().'&r=20')->assertSee('received your payment yet')->assertDontSee('http-equiv="refresh"', false);

        // Paid, but the webhook never arrived: the order page's own check with PayMongo records it.
        $paid = true;
        \Illuminate\Support\Facades\Cache::flush();
        $this->get($order->publicPath())->assertSee('your order is paid');
        $this->assertSame('paid', $order->refresh()->status);
        $this->assertSame('card', $order->payment_method);
        Http::assertSent(fn ($r) => $r->method() === 'GET' && str_ends_with($r->url(), '/checkout_sessions/cs_test_123'));
    }

    public function test_a_site_that_never_switched_the_shop_on_creates_and_shows_nothing(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        \Illuminate\Support\Facades\Cache::flush();
        Shop::flushState();
        config(['shop.enabled' => false]);
        $staff = User::query()->create(['name' => 'Editor', 'email' => 'editor@jflsolar.test', 'password' => 'a long test password', 'role' => 'editor', 'active' => true, 'must_change_password' => false]);

        $this->get('/checkout/done?order=JFL-000000-AAAA&t='.str_repeat('a', 40))->assertNotFound();
        $this->get('/orders/JFL-000000-AAAA?t='.str_repeat('a', 40))->assertNotFound();
        $this->actingAs($staff)->get('/admin/orders')->assertNotFound();
        $this->actingAs($staff)->get('/admin')->assertOk()->assertDontSee('/admin/orders', false);
        Shop::flushState();
        $this->assertFalse(Schema::hasTable('orders'));
    }

    public function test_signed_in_staff_preview_the_shop_while_it_is_off_and_nothing_is_saved(): void
    {
        config(['shop.enabled' => false]);
        $p = $this->product();
        $staff = User::query()->create(['name' => 'Editor', 'email' => 'editor@jflsolar.test', 'password' => 'a long test password', 'role' => 'editor', 'active' => true, 'must_change_password' => false]);

        // Customers: no shop at all.
        $this->get('/products/'.$p->slug)->assertDontSee('Add to cart');
        $this->get('/cart')->assertNotFound();

        // Staff: full preview, clearly labelled; checkout stops before payment.
        Http::fake();
        $this->actingAs($staff)->get('/products/'.$p->slug)->assertSee('Add to cart')->assertSee('Staff preview');
        $this->actingAs($staff)->post('/cart/add', ['product' => $p->id, 'quantity' => 2]);
        $this->actingAs($staff)->get('/cart')->assertOk()->assertSee('Staff preview')->assertSee('₱960');
        $this->actingAs($staff)->post('/checkout', $this->checkoutForm())->assertRedirect('/checkout')->assertSessionHas('checkout_preview');
        $this->assertSame(0, Order::query()->count());
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'paymongo'));

        // Switched off entirely: staff see nothing either.
        config(['shop.staff_preview' => false]);
        $this->actingAs($staff)->get('/cart')->assertNotFound();
    }

    public function test_pay_at_pickup_reserves_the_order_without_paymongo_and_alerts_the_owner(): void
    {
        Mail::fake();
        Http::fake();
        $p = $this->product();
        $this->post('/cart/add', ['product' => $p->id, 'quantity' => 2]);
        $this->get('/checkout')->assertSee('Cash')->assertSee('GCash')->assertSee('Bank transfer')->assertSee('Check')->assertSee('Pay online now');
        $response = $this->post('/checkout', $this->checkoutForm(['payment' => 'gcash']));

        $order = Order::query()->latest('id')->firstOrFail();
        $response->assertRedirect($order->publicPath());
        $this->assertSame('reserved', $order->status);
        $this->assertSame('in_person', $order->payment_mode);
        $this->assertSame('gcash', $order->payment_preference);
        $this->assertSame('warehouse', $order->pickup_location);
        $this->assertNull($order->paid_at);
        $this->assertNull($order->paymongo_checkout_id);
        $this->assertSame([], session('cart'));
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'paymongo'));
        Mail::assertSent(NewOrder::class, fn ($m) => str_contains($m->envelope()->subject, 'pay at pickup'));
        $this->get($order->publicPath())->assertSee('Nothing has been charged')->assertSee('by GCash')->assertSee('We send our GCash number')->assertSee('at our warehouse')->assertSee('Please call our Sales team before pickup')->assertSee('₱960');
    }

    public function test_shop_runs_without_paymongo_when_paying_at_pickup(): void
    {
        config(['shop.paymongo.secret_key' => '', 'shop.paymongo.webhook_secret' => '']);
        $p = $this->product();
        $this->get('/products/'.$p->slug)->assertSee('Add to cart')->assertSee('Order online, then pay by cash, GCash, bank transfer, check or card');
        $this->post('/cart/add', ['product' => $p->id, 'quantity' => 1]);
        $this->get('/checkout')->assertSee('Bank transfer')->assertDontSee('Pay online now');
        // "online" cannot be forced when PayMongo is not set up.
        $this->post('/checkout', $this->checkoutForm(['payment' => 'online']))->assertSessionHasErrors('payment');
        $this->post('/checkout', $this->checkoutForm(['payment' => 'check']));
        $this->assertSame('check', Order::query()->latest('id')->value('payment_preference'));

        // Only the methods the owner offers.
        config(['shop.in_person_methods' => ['cash']]);
        $this->get('/checkout')->assertDontSee('Bank transfer');
        $this->assertStringNotContainsString('paymongo', (string) $this->get('/checkout')->headers->get('Content-Security-Policy'));

        config(['shop.pay_in_person' => false]);
        $this->get('/cart')->assertNotFound();   // no way to pay → no shop
    }

    public function test_staff_record_counter_payment_for_pay_at_pickup_orders(): void
    {
        $p = $this->product();
        $this->post('/cart/add', ['product' => $p->id, 'quantity' => 2]);
        $this->post('/checkout', $this->checkoutForm(['payment' => 'cash']));
        $order = Order::query()->latest('id')->firstOrFail();
        $staff = User::query()->create(['name' => 'Editor', 'email' => 'editor@jflsolar.test', 'password' => 'a long test password', 'role' => 'editor', 'active' => true, 'must_change_password' => false]);

        $this->actingAs($staff)->get('/admin/orders')->assertSee($order->reference)->assertSee('Pays at pickup');
        $this->actingAs($staff)->get('/admin/orders/'.$order->id)->assertSee('Paid &amp; picked up', false);
        // The payment method is required.
        $this->actingAs($staff)->post('/admin/orders/'.$order->id, ['status' => 'completed', 'lock_version' => 0])->assertSessionHas('status_error');
        $this->assertNull($order->refresh()->paid_at);

        $this->actingAs($staff)->post('/admin/orders/'.$order->id, ['status' => 'ready', 'lock_version' => 0]);
        $this->actingAs($staff)->post('/admin/orders/'.$order->id, ['status' => 'completed', 'counter_method' => 'cash', 'lock_version' => 1])->assertSessionHas('status', 'Payment recorded — order picked up.');
        $order->refresh();
        $this->assertSame('completed', $order->status);
        $this->assertSame('cash', $order->payment_method);
        $this->assertSame(960.0, $order->amount_paid_php);
        $this->assertNotNull($order->paid_at);
    }

    public function test_reservations_per_visitor_are_limited(): void
    {
        config(['shop.reservations_per_day' => 1]);
        $p = $this->product();
        $this->post('/cart/add', ['product' => $p->id, 'quantity' => 1]);
        $this->post('/checkout', $this->checkoutForm(['payment' => 'cash']));
        $this->post('/cart/add', ['product' => $p->id, 'quantity' => 1]);
        $this->post('/checkout', $this->checkoutForm(['payment' => 'cash']))->assertSessionHas('checkout_error');
        $this->assertSame(1, Order::query()->count());
    }

    public function test_privacy_and_terms_explain_online_orders_only_while_the_shop_is_on(): void
    {
        $this->get('/privacy')->assertSee('Online orders')->assertSee('PayMongo');
        $this->get('/terms')->assertSee('Online orders');
        $this->get('/robots.txt')->assertOk();
        // The checkout form is answered with a redirect to PayMongo, which CSP form-action must allow.
        $this->assertStringContainsString("form-action 'self' https://checkout.paymongo.com", $this->get('/checkout')->headers->get('Content-Security-Policy'));
        config(['shop.enabled' => false]);
        $this->get('/privacy')->assertDontSee('Online orders');
        $this->get('/terms')->assertDontSee('Online orders');
    }

    public function test_signature_check_uses_the_live_part_with_live_keys(): void
    {
        config(['shop.paymongo.secret_key' => 'sk_live_abc']);
        $raw = '{"a":1}';
        $t = time();
        $sig = hash_hmac('sha256', $t.'.'.$raw, self::SECRET);
        $this->assertTrue(PayMongo::signatureValid($raw, "t={$t},te=,li={$sig}"));
        $this->assertFalse(PayMongo::signatureValid($raw, "t={$t},te={$sig},li="));
    }

    public function test_pickup_is_only_at_the_warehouse_and_customers_are_told_to_call_sales_first(): void
    {
        Mail::fake();
        Http::fake();
        $settings = PublicContent::settings();
        $p = $this->product();
        $this->get('/products/'.$p->slug)->assertSee('pick up at our warehouse')->assertSee('call our Sales team before you come');
        $this->post('/cart/add', ['product' => $p->id, 'quantity' => 1]);
        $this->get('/checkout')
            ->assertSee('Pickup only at')
            ->assertSee($settings->warehouse_address)
            ->assertDontSee('name="pickup" value="office"', false)
            ->assertSee('Please call our Sales team before pickup')->assertSee('For directions, search')->assertSee('waze.com/ul?q=', false);

        // A forged choice of the office still books the warehouse.
        $this->post('/checkout', $this->checkoutForm(['payment' => 'cash', 'pickup' => 'office']));
        $this->assertSame('warehouse', Order::query()->latest('id')->firstOrFail()->pickup_location);
        $this->post('/cart/add', ['product' => $p->id, 'quantity' => 1]);

        // Owner can offer both places again from the .env.
        config(['shop.pickup_locations' => ['warehouse', 'office']]);
        $this->get('/checkout')->assertSee('name="pickup" value="office"', false);
    }

    public function test_customer_gets_a_printable_summary_and_staff_get_the_same_summary_by_email(): void
    {
        Mail::fake();
        Http::fake();
        $p = $this->product(['brand' => 'Leikeking', 'model' => 'DC-2P-25A']);
        $this->post('/cart/add', ['product' => $p->id, 'quantity' => 2]);
        $this->post('/checkout', $this->checkoutForm(['payment' => 'cash', 'notes' => 'Saturday morning please']));
        $order = Order::query()->latest('id')->firstOrFail();

        // The confirmation page shows everything they filled in, and a print button opening a new window.
        $this->get($order->publicPath())->assertSee('Your details')->assertSee('Juan Dela Cruz')->assertSee('juan@example.com')
            ->assertSee('Saturday morning please')->assertSee('Print order summary')->assertSee($order->printPath(), false)->assertSee('target="_blank"', false);

        // The printable summary: all details, items and total; private to the order link.
        $this->get($order->printPath())->assertOk()->assertSee('Order summary')->assertSee($order->reference)->assertSee('0917 123 4567')
            ->assertSee('juan@example.com')->assertSee('Saturday morning please')->assertSee('Pay at pickup by Cash')->assertSee('Our warehouse')
            ->assertSee('Leikeking DC-2P-25A')->assertSee('₱960')->assertSee('window.print()', false)
            ->assertSee('JFL Solar &amp; Renewables', false)->assertSee('google.com/maps/search/?api=1&amp;query=JFL%20Solar%20%26%20Renewables', false)->assertSee('waze.com/ul?q=', false)->assertSee('not an official receipt');
        $this->get('/orders/'.$order->reference.'/print?t='.str_repeat('a', 40))->assertNotFound();

        // Staff email: the same summary as HTML, plus the printable and admin links.
        Mail::assertSent(NewOrder::class, function (NewOrder $m) use ($order) {
            $html = $m->render();

            return str_contains($html, 'Order summary') && str_contains($html, 'Saturday morning please') && str_contains($html, '0917 123 4567')
                && str_contains($html, 'Leikeking DC-2P-25A') && str_contains($html, '/admin/orders/'.$order->id) && str_contains($html, '/print?t=');
        });
    }

    public function test_staff_preview_shows_the_confirmation_and_printable_summary_and_emails_a_marked_copy(): void
    {
        Mail::fake();
        Http::fake();
        config(['shop.enabled' => false]);
        $p = $this->product();
        $staff = User::query()->create(['name' => 'Editor', 'email' => 'editor@jflsolar.test', 'password' => 'a long test password', 'role' => 'editor', 'active' => true, 'must_change_password' => false]);
        $this->actingAs($staff)->post('/cart/add', ['product' => $p->id, 'quantity' => 2]);
        $this->actingAs($staff)->post('/checkout', $this->checkoutForm(['payment' => 'gcash']))->assertRedirect('/checkout/preview');
        $this->assertSame(0, Order::query()->count());

        $this->actingAs($staff)->get('/checkout/preview')->assertOk()->assertSee('Staff preview')->assertSee('Thank you — your order is placed')->assertSee('/checkout/preview/print');
        $this->actingAs($staff)->get('/checkout/preview/print')->assertOk()->assertSee('Order summary')->assertSee('Juan Dela Cruz')->assertSee('Pay at pickup by GCash');
        Mail::assertSent(NewOrder::class, fn (NewOrder $m) => str_starts_with($m->envelope()->subject, '[Staff preview') && str_contains($m->render(), 'not a real order'));

        // Customers can't see a preview.
        auth()->logout();
        $this->get('/checkout/preview')->assertNotFound();
    }

    public function test_card_payment_is_offered_only_for_warehouse_pickup(): void
    {
        Http::fake();
        $p = $this->product();
        $this->post('/cart/add', ['product' => $p->id, 'quantity' => 1]);
        $this->get('/checkout')->assertSee('Card (credit or debit)')->assertSee('available at our warehouse only');
        $this->post('/checkout', $this->checkoutForm(['payment' => 'card']));
        $order = Order::query()->latest('id')->firstOrFail();
        $this->assertSame('card', $order->payment_preference);
        $this->assertSame('warehouse', $order->pickup_location);

        // With both places offered, card + office is refused.
        config(['shop.pickup_locations' => ['warehouse', 'office']]);
        $this->post('/cart/add', ['product' => $p->id, 'quantity' => 1]);
        $this->post('/checkout', $this->checkoutForm(['payment' => 'card', 'pickup' => 'office']))->assertSessionHasErrors('payment');
        $this->assertSame(1, Order::query()->count());

        // Without a warehouse address, card is not offered at all.
        \App\Models\SiteSetting::query()->update(['warehouse_address' => null]);
        PublicContent::flush();
        $this->get('/checkout')->assertDontSee('Card (credit or debit)');
    }

    public function test_order_summary_links_use_jflsolar_ph_even_if_app_url_is_a_temporary_address(): void
    {
        config(['site.url' => 'https://temp-host.hostingersite.com']);
        $this->app->instance('request', \Illuminate\Http\Request::create('https://jflsolar.ph/checkout'));
        $this->assertSame('https://jflsolar.ph/x', \App\Support\Site::publicUrl('/x'));
        // A forged host never makes it into links.
        $this->app->instance('request', \Illuminate\Http\Request::create('https://evil.example/checkout'));
        $this->assertSame('https://temp-host.hostingersite.com/x', \App\Support\Site::publicUrl('/x'));
    }
}
