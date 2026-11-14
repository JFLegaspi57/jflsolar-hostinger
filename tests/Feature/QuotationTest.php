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

    private function product(string $slug, string $name, ?float $price, string $mode = 'fixed', string $basis = 'Pickup price per piece', string $availability = 'available'): Product
    {
        $category = ProductCategory::query()->firstOrCreate(['slug' => 'inverters-q'], ['name' => 'Inverters', 'status' => 'published', 'sort_order' => 50]);
        $p = Product::query()->create(['slug' => $slug, 'name' => $name, 'brand' => 'B', 'model' => 'M', 'category_id' => $category->id, 'summary' => '', 'description' => 'x', 'specs' => [],
            'pricing_mode' => $mode, 'price_php' => $price, 'price_basis' => $basis, 'price_is_sample' => false, 'availability' => $availability, 'status' => 'published', 'is_sample' => false]);
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

        // Remove a line.
        $this->get('/quotation/'.$q->id)->assertSee('value="remove:2"', false)->assertSee('Remove<span class="sr-only"> Battery 15 kWh from this quotation</span>', false);
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

    public function test_only_products_available_to_order_can_be_picked(): void
    {
        $ok = $this->product('in-stock', 'Inverter in stock', 50000);
        $out = $this->product('sold-out', 'Inverter sold out', 48000, 'fixed', 'Pickup price per piece', 'unavailable');
        $ask = $this->product('ask-first', 'Battery ask first', 90000, 'fixed', 'Pickup price per piece', 'on_request');
        $this->actingAs($this->member(), 'member');
        $this->post('/quotation');
        $q = MemberQuote::query()->firstOrFail();

        $this->get('/quotation/'.$q->id)->assertSee('Inverter in stock')->assertDontSee('Inverter sold out')->assertDontSee('Battery ask first');
        $this->post('/quotation/'.$q->id, $this->form($q, ['intent' => 'add_product', 'add_product_id' => $out->id]))
            ->assertSessionHas('quote_error', 'That product is currently not available to order. Please choose another, or add it as your own item.');
        $this->post('/quotation/'.$q->id, $this->form($q, ['intent' => 'add_product', 'add_product_id' => $ask->id]))->assertSessionHas('quote_error');
        $this->post('/quotation', ['product_id' => $out->id]);
        $this->assertSame([], MemberQuote::query()->latest('id')->first()->items);

        // A product that stops being available stays on the quotation, flagged and not counted.
        $this->post('/quotation/'.$q->id, $this->form($q, ['intent' => 'add_product', 'add_product_id' => $ok->id, 'add_qty' => 2]));
        $ok->forceFill(['availability' => 'unavailable'])->save();
        PublicContent::flush();
        $sum = Quotations::lines($q->refresh());
        $this->assertTrue($sum['lines'][0]['unavailable']);
        $this->assertSame(0.0, $sum['total']);
        $this->get('/quotation/'.$q->id)->assertSee('Currently not available');
        $this->assertStringContainsString('Inverter in stock (currently not available)', Quotations::message($q));
    }

    public function test_lines_and_the_product_list_follow_the_shop_category_order(): void
    {
        $batteries = ProductCategory::query()->create(['slug' => 'batteries-q', 'name' => 'Batteries', 'status' => 'published', 'sort_order' => 1]);
        $panels = ProductCategory::query()->create(['slug' => 'panels-q', 'name' => 'Solar panels', 'status' => 'published', 'sort_order' => 2]);
        $mk = fn ($cat, $slug, $name) => Product::query()->create(['slug' => $slug, 'name' => $name, 'brand' => 'B', 'model' => 'M', 'category_id' => $cat->id, 'summary' => '', 'description' => 'x', 'specs' => [],
            'pricing_mode' => 'fixed', 'price_php' => 1000, 'price_basis' => 'Pickup price per piece', 'price_is_sample' => false, 'availability' => 'available', 'status' => 'published', 'is_sample' => false]);
        $panel = $mk($panels, 'panel-x', 'Panel X');
        $battery = $mk($batteries, 'battery-x', 'Battery X');
        $panel2 = $mk($panels, 'panel-y', 'Panel Y');
        PublicContent::flush();

        $this->actingAs($this->member(), 'member');
        $this->post('/quotation');
        $q = MemberQuote::query()->firstOrFail();
        $this->post('/quotation/'.$q->id, $this->form($q, ['intent' => 'add_custom', 'custom_name' => 'Labour']));
        foreach ([$panel, $battery, $panel2] as $p) {
            $this->post('/quotation/'.$q->id, $this->form($q, ['intent' => 'add_product', 'add_product_id' => $p->id]));
        }
        $sum = Quotations::lines($q->refresh());
        $this->assertSame(['Battery X', 'Panel X', 'Panel Y', 'Labour'], array_column($sum['lines'], 'name'));
        $this->assertSame(['Batteries', 'Solar panels', 'Solar panels', Quotations::OWN_ITEMS], array_column($sum['lines'], 'category'));
        $this->get('/quotation/'.$q->id)->assertSeeInOrder(['<optgroup label="Batteries">', '<optgroup label="Solar panels">'], false)
            ->assertSeeInOrder(['scope="colgroup">Batteries</th>', 'Battery X', 'scope="colgroup">Solar panels</th>', 'Panel X', 'Panel Y', 'scope="colgroup">Your own items</th>'], false);
        $this->assertStringContainsString("BATTERIES\n1. Battery X", Quotations::message($q));
        // Removing a line in the middle removes that line, whatever the display order.
        $labourIndex = collect($q->items)->search(fn ($it) => $it['kind'] === 'custom');
        $this->post('/quotation/'.$q->id, $this->form($q, ['intent' => 'remove:'.$labourIndex]))->assertSessionHas('quote_status', 'Removed “Labour”.');
        $this->assertSame(['Battery X', 'Panel X', 'Panel Y'], array_column(Quotations::lines($q->refresh())['lines'], 'name'));
    }

    public function test_a_quotation_can_be_renamed_from_the_top_of_the_page(): void
    {
        $this->actingAs($this->member(), 'member');
        $this->post('/quotation');
        $q = MemberQuote::query()->firstOrFail();
        $this->get('/quotation/'.$q->id)->assertSee('Rename')->assertSee('form="mq-form" value="My quotation 1"', false)->assertSee('value="rename"', false);
        $this->post('/quotation/'.$q->id, $this->form($q, ['intent' => 'rename', 'title' => '  Warehouse roof 30 kW ']))
            ->assertRedirect('/quotation/'.$q->id)->assertSessionHas('quote_status', 'Renamed to “Warehouse roof 30 kW”.');
        $this->assertSame('Warehouse roof 30 kW', $q->refresh()->title);
        $this->post('/quotation/'.$q->id, $this->form($q, ['intent' => 'rename', 'title' => '']))->assertSessionHas('quote_error', 'Type a name for your quotation.');
        $this->assertSame('Warehouse roof 30 kW', $q->refresh()->title);
        $this->get('/quotation')->assertSee('Warehouse roof 30 kW');

        // From the list of quotations.
        $this->get('/quotation')->assertSee('action="/quotation/'.$q->id.'/rename"', false);
        $this->post('/quotation/'.$q->id.'/rename', ['title' => 'Warehouse roof 40 kW'])->assertRedirect('/quotation#q-'.$q->id)
            ->assertSessionHas('quote_status', 'Renamed “Warehouse roof 30 kW” to “Warehouse roof 40 kW”.');
        $this->assertSame('Warehouse roof 40 kW', $q->refresh()->title);
        $this->post('/quotation/'.$q->id.'/rename', ['title' => ' '])->assertSessionHas('quote_error');
        auth('member')->logout();
        $this->flushSession();
        $this->actingAs($this->member('other@example.com'), 'member');
        $this->post('/quotation/'.$q->id.'/rename', ['title' => 'Hijack'])->assertNotFound();
        $this->assertSame('Warehouse roof 40 kW', $q->refresh()->title);
    }
}
