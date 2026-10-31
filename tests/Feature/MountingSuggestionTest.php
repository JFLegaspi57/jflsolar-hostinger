<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\PackageBuilder;
use App\Support\PublicContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MountingSuggestionTest extends TestCase
{
    use RefreshDatabase;

    private function product(string $cat, string $slug, string $name, float $price): Product
    {
        $category = ProductCategory::query()->firstOrCreate(['slug' => $cat], ['name' => ucfirst(str_replace('-', ' ', $cat)), 'status' => 'published', 'sort_order' => 50]);

        return Product::query()->updateOrCreate(['slug' => $slug], ['name' => $name, 'brand' => 'Generic', 'model' => '', 'category_id' => $category->id, 'summary' => '', 'description' => 'x', 'specs' => [],
            'pricing_mode' => 'fixed', 'price_php' => $price, 'price_basis' => 'Pickup price per piece', 'price_is_sample' => false, 'availability' => 'available', 'status' => 'published', 'is_sample' => false]);
    }

    public function test_rule_for_n_panels(): void
    {
        $q = collect(PackageBuilder::mountingFor(10))->pluck('quantity', 'slug')->all();
        $this->assertSame(['railing' => 10, 'splice-connector' => 8, 'l-foot' => 30, 'mid-clamp' => 20, 'end-clamp' => 4], $q);
        $this->assertSame(12, collect(PackageBuilder::mountingFor(14))->firstWhere('slug', 'splice-connector')['quantity']);   // 11.2 → 12
        $this->assertSame(16, collect(PackageBuilder::mountingFor(20))->firstWhere('slug', 'splice-connector')['quantity']);
        $this->assertSame([], PackageBuilder::mountingFor(0));
    }

    public function test_build_your_own_and_calculator_show_the_suggestion(): void
    {
        $this->seed();
        config(['shop.enabled' => true]);
        Product::query()->update(['availability' => 'on_request']);
        $this->product('inverters', 'inv-6', '6kW Hybrid Inverter', 40000);
        $this->product('solar-panels', 'pan-620', '620W Panel', 5900);
        $this->product('batteries', 'bat-16', '16kWh Battery', 106000);
        $rail = $this->product('mounting-hardware', 'railing', 'Railing', 550);
        $this->product('mounting-hardware', 'l-foot', 'L-foot', 75);
        $this->product('mounting-hardware', 'mid-clamp', 'Mid-clamp', 50);
        $this->product('mounting-hardware', 'end-clamp', 'End Clamp', 55);
        $this->product('mounting-hardware', 'splice-connector', 'Splice Connector', 75);
        PublicContent::flush();

        // 10 panels: 10 × 550 + 8 × 75 + 30 × 75 + 20 × 50 + 4 × 55 = 9,570. Suggestion only: the total stays 205,000.
        $html = $this->get('/packages/build-your-own')->assertOk()->assertSee('Recommended mounting for')->assertSee('₱9,570')->assertSee('8 per 10 railings')->assertSee('₱205,000')
            ->assertSee('3 per railing')->assertSee('data-mount-apply', false)->getContent();
        $this->assertMatchesRegularExpression('#data-id="'.$rail->id.'"[^>]*>.*?data-mount-qty>10<#s', $html);
        $this->assertStringContainsString('value="0" data-addon', $html);   // nothing added until the customer asks

        $this->assertMatchesRegularExpression('#<details data-mount[^>]*\sopen\s*>#', $html);   // open while the customer builds

        // After Add to Basket the suggestion is shown closed.
        $this->post('/packages/build-your-own/basket', ['quantity' => [0 => 1, 1 => 10, 2 => 1], 'addon' => [$rail->id => 10]])->assertSessionHas('pkg_added');
        $after = $this->get('/packages/build-your-own')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#<details data-mount[^>]*>#', $after);
        $this->assertDoesNotMatchRegularExpression('#<details data-mount[^>]*\sopen\s*>#', $after);

        // The calculator result shows the same rule.
        $this->get('/pv-calculator?type=hybrid&mode=bill&bill=8000&rate=12.5&day=50')->assertOk()
            ->assertSee('Mounting (suggested)')->assertSee('10 railings · 8 splice connectors · 30 l-feet')->assertSee('20 mid clamps · 4 end clamps');
    }
}
