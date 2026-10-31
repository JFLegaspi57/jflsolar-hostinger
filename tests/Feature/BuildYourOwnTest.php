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
            ->assertSee('Build Your Own')->assertSee('Your system')->assertSee('BYO Inverter 8kW')->assertDontSee('BYO Inverter Quote')
            ->assertSee('₱205,000')   // 1 × 40,000 + 10 × 5,900 + 1 × 106,000
            ->assertSee('BYO Clamp')->getContent();
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
}
