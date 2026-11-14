<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\Member;
use App\Models\MemberQuote;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\PublicContent;
use App\Support\Quotations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        PublicContent::flush();
    }

    private function product(string $slug, string $name, ?float $price, string $mode = 'fixed', string $basis = 'Pickup price per piece'): Product
    {
        $category = ProductCategory::query()->firstOrCreate(['slug' => 'inverters-q'], ['name' => 'Inverters', 'status' => 'published', 'sort_order' => 50]);
        $p = Product::query()->create(['slug' => $slug, 'name' => $name, 'brand' => 'B', 'model' => 'M', 'category_id' => $category->id, 'summary' => '', 'description' => 'x', 'specs' => [],
            'pricing_mode' => $mode, 'price_php' => $price, 'price_basis' => $basis, 'price_is_sample' => false, 'availability' => 'available', 'status' => 'published', 'is_sample' => false]);
        PublicContent::flush();

        return $p;
    }

    private function member(string $email = 'maria@example.com'): Member
    {
        $m = new Member;
        $m->forceFill(['name' => 'Maria Santos', 'email' => $email, 'phone' => '0917 123 4567', 'password' => 'sunny days on the roof', 'status' => 'active'])->save();

        return $m;
    }

    private function form(MemberQuote $q, array $extra = []): array
    {
        $q->refresh();
        $items = [];
        foreach ((array) $q->items as $i => $it) {
            $items[$i] = ['qty' => $it['qty'], 'note' => $it['note'] ?? ''] + (($it['kind'] ?? '') === 'custom' ? ['name' => $it['name'], 'unit' => $it['unit'] ?? ''] : []);
        }

        return array_merge(['title' => $q->title, 'location' => $q->location, 'phone' => $q->phone, 'notes' => $q->notes, 'items' => $items, 'intent' => 'save'], $extra);
    }

    public function test_visitors_see_how_it_works_and_are_invited_to_sign_up(): void
    {
        $this->get('/quotation')->assertOk()->assertSee('Make a Quotation')->assertSee('How it works')
            ->assertSee('href="/signup?next=%2Fquotation"', false);
        $this->post('/quotation')->assertRedirect('/login?next=%2Fquotation');
        $this->get('/quotation/1')->assertRedirect('/login?next=%2Fquotation%2F1');
        $this->get('/products')->assertSee('href="/quotation"', false);
    }

    public function test_a_member_builds_customises_and_sends_a_quotation(): void
    {
        $inv = $this->product('hybrid-8k', 'Hybrid inverter 8 kW', 65000);
        $cable = $this->product('pv-cable', 'PV cable 6 mm²', 85, 'fixed', 'Price per metre');
        $quoteOnly = $this->product('big-battery', 'Battery 15 kWh', null, 'quote');
        $m = $this->member();
        $this->actingAs($m, 'member');

        $this->post('/quotation')->assertRedirect();
        $q = MemberQuote::query()->firstOrFail();
        $this->assertSame('My quotation 1', $q->title);
        $this->assertSame('0917 123 4567', $q->phone);
        $this->get('/quotation/'.$q->id)->assertOk()->assertSee('Choose a product…')->assertSee('Hybrid inverter 8 kW — ₱65,000')->assertSee('Battery 15 kWh — price to be confirmed');

        // Add products (adding the same one again adds to its quantity), an own item, then edit.
        $this->post('/quotation/'.$q->id, $this->form($q, ['intent' => 'add_product', 'add_product_id' => $inv->id, 'add_qty' => 1]));
        $this->post('/quotation/'.$q->id, $this->form($q, ['intent' => 'add_product', 'add_product_id' => $inv->id, 'add_qty' => 1]));
        $this->post('/quotation/'.$q->id, $this->form($q, ['intent' => 'add_product', 'add_product_id' => $cable->id, 'add_qty' => 30]));
        $this->post('/quotation/'.$q->id, $this->form($q, ['intent' => 'add_product', 'add_product_id' => $quoteOnly->id, 'add_qty' => 1]));
        $this->post('/quotation/'.$q->id, $this->form($q, ['intent' => 'add_custom', 'custom_name' => 'Installation labour', 'custom_qty' => 1, 'custom_unit' => 'lot']));
        $this->assertCount(4, $q->refresh()->items);
        $this->assertSame(2, $q->items[0]['qty']);

        $form = $this->form($q, ['title' => 'Our 8 kW hybrid', 'location' => 'Lipa, Batangas', 'notes' => 'Delivery in December please.']);
        $form['items'][1]['qty'] = 40;
        $form['items'][3]['name'] = 'Installation labour and commissioning';
        $form['items'][0]['note'] = 'White colour if possible';
        $this->post('/quotation/'.$q->id, $form)->assertRedirect('/quotation/'.$q->id);
        $sum = Quotations::lines($q->refresh());
        $this->assertSame(65000.0 * 2 + 85.0 * 40, $sum['total']);
        $this->assertSame(2, $sum['unpriced']);
        $this->assertSame('metres', $sum['lines'][1]['unit']);

        // Move, remove.
        $this->post('/quotation/'.$q->id, $this->form($q, ['intent' => 'down:0']));
        $this->assertSame($cable->id, $q->refresh()->items[0]['product_id']);
        $this->post('/quotation/'.$q->id, $this->form($q, ['intent' => 'remove:2']))->assertSessionHas('quote_status', 'Removed “Battery 15 kWh”.');
        $this->assertCount(3, $q->refresh()->items);

        $this->get('/quotation/'.$q->id)->assertSee('Our 8 kW hybrid')->assertSee('₱133,400')->assertSee('+ 1 item to be quoted by JFL Solar')->assertSee('White colour if possible');
        $this->get('/quotation/'.$q->id.'/print')->assertOk()->assertSee('Quotation request')->assertSee($q->reference())->assertSee('Installation labour and commissioning')->assertSee('not a formal quotation');
        $this->get('/quotation')->assertSee('Our 8 kW hybrid')->assertSee('Draft');

        // Send: lands in the staff inbox like a contact-form inquiry.
        $this->post('/quotation/'.$q->id, $this->form($q, ['intent' => 'send']))->assertRedirect('/quotation/'.$q->id.'#send')->assertSessionHas('quote_sent');
        $inquiry = Inquiry::query()->firstOrFail();
        $this->assertSame('equipment', $inquiry->inquiry_type);
        $this->assertSame('maria@example.com', $inquiry->email);
        $this->assertSame('Lipa, Batangas', $inquiry->location);
        $this->assertStringContainsString($q->reference(), $inquiry->item_label);
        $this->assertStringContainsString('PV cable 6 mm² — 40 metres — ₱85 each = ₱3,400', $inquiry->message);
        $this->assertStringContainsString('Installation labour and commissioning — 1 lot — to be quoted (own item)', $inquiry->message);
        $this->assertStringContainsString('Delivery in December please.', $inquiry->message);
        $this->assertNotNull($q->refresh()->sent_at);
        $this->assertSame($inquiry->id, $q->inquiry_id);

        // Changing it afterwards is flagged until it is sent again.
        $this->post('/quotation/'.$q->id, $this->form($q, ['notes' => 'Delivery in January instead.']));
        $this->assertTrue($q->refresh()->edited_after_send);
        $this->get('/quotation')->assertSee('Changed since sending');

        // Copy and delete.
        $this->post('/quotation/'.$q->id.'/duplicate')->assertRedirect();
        $copy = MemberQuote::query()->where('id', '!=', $q->id)->firstOrFail();
        $this->assertSame('Copy of Our 8 kW hybrid', $copy->title);
        $this->assertNull($copy->sent_at);
        $this->post('/quotation/'.$copy->id.'/delete')->assertRedirect('/quotation');
        $this->assertSame(1, MemberQuote::query()->count());
    }

    public function test_sending_needs_items_location_and_phone(): void
    {
        $m = $this->member();
        $this->actingAs($m, 'member');
        $this->post('/quotation');
        $q = MemberQuote::query()->firstOrFail();
        $this->post('/quotation/'.$q->id, $this->form($q, ['intent' => 'send', 'phone' => '']))->assertSessionHas('quote_error');
        $msg = session('quote_error');
        $this->assertStringContainsString('Add at least one product or item.', $msg);
        $this->assertStringContainsString('city or province', $msg);
        $this->assertStringContainsString('phone number', $msg);
        $this->assertSame(0, Inquiry::query()->count());
    }

    public function test_members_only_see_their_own_quotations(): void
    {
        $this->actingAs($this->member(), 'member');
        $this->post('/quotation');
        $q = MemberQuote::query()->firstOrFail();
        auth('member')->logout();
        $this->flushSession();

        $this->actingAs($this->member('other@example.com'), 'member');
        $this->get('/quotation/'.$q->id)->assertNotFound();
        $this->post('/quotation/'.$q->id, ['title' => 'Hijack', 'intent' => 'save'])->assertNotFound();
        $this->post('/quotation/'.$q->id.'/delete')->assertNotFound();
        $this->get('/quotation/'.$q->id.'/print')->assertNotFound();
        $this->get('/quotation')->assertDontSee($q->title);
        $this->assertSame('My quotation 1', $q->refresh()->title);
    }

    public function test_unknown_products_and_custom_names_are_rejected_politely(): void
    {
        $this->actingAs($this->member(), 'member');
        $this->post('/quotation');
        $q = MemberQuote::query()->firstOrFail();
        $this->post('/quotation/'.$q->id, $this->form($q, ['intent' => 'add_product', 'add_product_id' => 'not-a-product']))->assertSessionHas('quote_error', 'Choose a product from the list first.');
        $this->post('/quotation/'.$q->id, $this->form($q, ['intent' => 'add_custom', 'custom_name' => ' ']))->assertSessionHas('quote_error');
        $this->assertSame([], $q->refresh()->items);
        // A product added with ?product_id when starting a quotation.
        $p = $this->product('panel-650', 'Panel 650 W', 7500);
        $this->post('/quotation', ['product_id' => $p->id]);
        $this->assertSame($p->id, MemberQuote::query()->latest('id')->first()->items[0]['product_id']);
    }
}
