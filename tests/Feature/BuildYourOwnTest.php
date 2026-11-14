<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\PublicContent;
use App\Support\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildYourOwnTest extends TestCase
{
    use RefreshDatabase;

    private function product(string $cat, string $slug, string $name, float $price, string $mode = 'fixed'): Product
    {
        $category = ProductCategory::query()->firstOrCreate(['slug' => $cat], ['name' => ucfirst(str_replace('-', ' ', $cat)), 'status' => 'published', 'sort_order' => 50]);

        return Product::query()->create(['slug' => $slug, 'name' => $name, 'brand' => 'B', 'model' => 'M', 'category_id' => $category->id, 'summary' => '', 'description' => 'x', 'specs' => [],
            'pricing_mode' => $mode, 'price_php' => $mode === 'fixed' ? $price : null, 'price_basis' => 'Pickup price per piece', 'price_is_sample' => false, 'availability' => 'available', 'status' => 'published', 'is_sample' => false]);
    }

    public function test_build_your_own_page_lists_parts_cheapest_first_and_adds_to_basket(): void
    {
        $this->seed();
        config(['shop.enabled' => true]);
        Product::query()->update(['availability' => 'on_request']);   // only the products below are sellable
        $inv8 = $this->product('inverters', 'byo-inv-8', 'BYO Inverter 8kW', 55000);
        $inv6 = $this->product('inverters', 'byo-inv-6', 'BYO Inverter 6kW', 40000);
        $this->product('inverters', 'byo-inv-q', 'BYO Inverter Quote', 0, 'quote');
        $panel = $this->product('solar-panels', 'byo-panel', 'BYO Panel 620W', 5900);
        $bat = $this->product('batteries', 'byo-bat', 'BYO Battery 16kWh', 106000);
        $clamp = $this->product('accessories', 'byo-clamp', 'BYO Clamp', 150);
        PublicContent::flush();

        $html = $this->get('/packages/build-your-own')->assertOk()
            ->assertSee('Build Your Own')->assertSee('Choose your own inverter, solar panels and battery')->assertSee('free technical support, from installation and commissioning to troubleshooting')->assertSee('Sagot ka namin!')->assertSee('Your system')->assertSee('BYO Inverter 8kW')->assertDontSee('BYO Inverter Quote')
            ->assertSee('₱205,000')   // 1 × 40,000 + 10 × 5,900 + 1 × 106,000
            ->assertSee('BYO Clamp')->assertSee('class="addon-chev', false)->assertSee('data-addons-toggle', false)->getContent();
        $this->assertLessThan(strpos($html, 'BYO Inverter 8kW'), strpos($html, 'BYO Inverter 6kW'));   // cheapest first = default

        $this->post('/packages/build-your-own/basket', ['product' => [0 => $inv8->id, 1 => $panel->id, 2 => $bat->id], 'quantity' => [0 => 1, 1 => 14, 2 => 2], 'addon' => [$clamp->id => 3]])
            ->assertRedirect('/packages/build-your-own#build')->assertSessionHas('pkg_added');
        $this->assertSame([$inv8->id => 1, $panel->id => 14, $bat->id => 2, $clamp->id => 3], Shop::raw());

        // Linked from the Packages menu and the packages page; listed in the sitemap.
        $this->get('/')->assertSee('href="/packages/build-your-own"', false)->assertSee('Build Your Own');
        $this->get('/packages')->assertSee('href="/packages/build-your-own"', false);
        $this->get('/sitemap.xml')->assertSee('/packages/build-your-own');
    }

    public function test_page_falls_back_to_a_quote_when_the_shop_is_off(): void
    {
        $this->seed();
        config(['shop.enabled' => false]);
        $this->get('/packages/build-your-own')->assertOk()->assertSee('Online ordering is not available right now')->assertDontSee('Add to Basket');
    }

    public function test_make_a_quotation_turns_the_build_into_a_quotation(): void
    {
        $this->seed();
        config(['shop.enabled' => true]);
        Product::query()->update(['availability' => 'on_request']);
        $inv = $this->product('inverters', 'byo-inv-6', 'BYO Inverter 6kW', 40000);
        $panel = $this->product('solar-panels', 'byo-panel', 'BYO Panel 620W', 5900);
        $bat = $this->product('batteries', 'byo-bat', 'BYO Battery 16kWh', 106000);
        $clamp = $this->product('accessories', 'byo-clamp', 'BYO Clamp', 150);
        PublicContent::flush();
        $form = ['product' => [0 => $inv->id, 1 => $panel->id, 2 => $bat->id], 'quantity' => [0 => 1, 1 => 12, 2 => 0], 'addon' => [$clamp->id => 4]];

        $this->get('/packages/build-your-own')->assertSee('formaction="/packages/build-your-own/quotation"', false)->assertSee('Make a Quotation');

        // A visitor signs in first; the choice is kept and becomes the quotation afterwards.
        $this->post('/packages/build-your-own/quotation', $form)->assertRedirect('/login?next=%2Fquotation%2Ffrom-build')->assertSessionHas('account_status');
        $m = new \App\Models\Member;
        $m->forceFill(['name' => 'Maria Santos', 'email' => 'maria@example.com', 'phone' => '0917 123 4567', 'password' => 'sunny days on the roof', 'status' => 'active'])->save();
        $this->actingAs($m, 'member');
        $res = $this->get('/quotation/from-build');
        $q = \App\Models\MemberQuote::query()->firstOrFail();
        $res->assertRedirect('/quotation/'.$q->id)->assertSessionHas('quote_status');
        $this->assertSame('Build Your Own package', $q->title);
        $this->assertSame('0917 123 4567', $q->phone);
        $this->assertSame([[$inv->id, 1], [$panel->id, 12], [$clamp->id, 4]], array_map(fn ($it) => [$it['product_id'], $it['qty']], $q->items));   // battery left out (0)
        $this->assertSame(40000.0 + 12 * 5900 + 4 * 150, \App\Support\Quotations::lines($q)['total']);
        $this->get('/quotation/from-build')->assertRedirect('/quotation');   // used once

        // Signed in already: straight to the new quotation; nothing goes into the basket.
        $this->post('/packages/build-your-own/quotation', $form)->assertRedirect();
        $this->assertSame('Build Your Own package 2', \App\Models\MemberQuote::query()->latest('id')->first()->title);
        $this->assertSame([], Shop::raw());

        // Nothing chosen: back to the builder with a message.
        $this->post('/packages/build-your-own/quotation', ['product' => [0 => $inv->id], 'quantity' => [0 => 0, 1 => 0, 2 => 0]])
            ->assertRedirect('/packages/build-your-own#build')->assertSessionHas('pkg_error');
    }
}
