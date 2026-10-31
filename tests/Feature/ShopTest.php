<?php

namespace Tests\Feature;

use App\Mail\NewOrder;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Support\PayMongo;
use App\Support\PublicContent;
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
        return array_merge(['name' => 'Juan Dela Cruz', 'phone' => '0917 123 4567', 'email' => 'juan@example.com', 'notes' => '', 'agree' => '1'], $changes);
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
        config(['shop.paymongo.secret_key' => '']);
        $this->get('/cart')->assertNotFound();
        $this->get('/products/'.$p->slug)->assertOk()->assertDontSee('Add to cart')->assertSee('Request a Product Quote');

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
        $this->actingAs($staff)->post('/admin/orders/'.$order->id, ['status' => 'ready', 'lock_version' => 0])->assertSessionHas('status');
        $this->assertSame('ready', $order->refresh()->status);
        // A stale form (old version) is refused instead of overwriting.
        $this->actingAs($staff)->post('/admin/orders/'.$order->id, ['status' => 'completed', 'lock_version' => 0])->assertSessionHas('status_error');
        $this->assertSame('ready', $order->refresh()->status);
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
}
