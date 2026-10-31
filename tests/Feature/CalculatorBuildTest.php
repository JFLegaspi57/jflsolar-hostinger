<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\PackageBuilder;
use App\Support\PublicContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculatorBuildTest extends TestCase
{
    use RefreshDatabase;

    private function product(string $cat, string $slug, string $name, float $price): Product
    {
        $category = ProductCategory::query()->firstOrCreate(['slug' => $cat], ['name' => ucfirst(str_replace('-', ' ', $cat)), 'status' => 'published', 'sort_order' => 50]);

        return Product::query()->create(['slug' => $slug, 'name' => $name, 'brand' => 'B', 'model' => '', 'category_id' => $category->id, 'summary' => '', 'description' => 'x', 'specs' => [],
            'pricing_mode' => 'fixed', 'price_php' => $price, 'price_basis' => 'Pickup price per piece', 'price_is_sample' => false, 'availability' => 'available', 'status' => 'published', 'is_sample' => false]);
    }

    private function catalog(): void
    {
        $this->seed();
        config(['shop.enabled' => true]);
        Product::query()->update(['availability' => 'on_request']);   // only the products below are sellable
        $this->product('inverters', 'inv-6', '6kW Single Phase Hybrid Inverter', 40000);
        $this->product('inverters', 'inv-8', '8kW Single Phase Hybrid Inverter', 55000);
        $this->product('inverters', 'inv-12', '12kW Single Phase Hybrid Inverter', 72000);
        $this->product('inverters', 'inv-off', '12kW Off-Grid Inverter', 50000);
        $this->product('solar-panels', 'pan-620', '620W Bifacial Solar Panel', 5900);
        $this->product('solar-panels', 'pan-650', '650W Bifacial Solar Panel', 6300);
        $this->product('batteries', 'bat-16', '16kWh LiFePO4 Battery', 106000);
        $this->product('batteries', 'bat-165', '16.5kWh LiFePO4 Battery', 107000);
        PublicContent::flush();
    }

    public function test_calculator_result_is_matched_to_products_we_sell(): void
    {
        $this->catalog();
        $hybrid = PackageBuilder::presetFromCalculator(['type' => 'hybrid', 'panel' => 620], ['inverter' => ['count' => 1, 'kw' => 8], 'kwp' => 7.44, 'battery' => 32]);
        $this->assertSame(['inverters' => ['inv-8', 1], 'solar-panels' => ['pan-620', 12], 'batteries' => ['bat-165', 2]], $hybrid);   // lowest cost per kWh

        $grid = PackageBuilder::presetFromCalculator(['type' => 'grid', 'panel' => 650], ['inverter' => ['count' => 1, 'kw' => 10], 'kwp' => 9.75, 'battery' => 0]);
        $this->assertSame(['inv-12', 1], $grid['inverters']);   // smallest that covers 10 kW; off-grid model not used
        $this->assertSame(['pan-650', 15], $grid['solar-panels']);
        $this->assertSame(0, $grid['batteries'][1]);   // grid-tied: no battery

        $big = PackageBuilder::presetFromCalculator(['type' => 'hybrid', 'panel' => 620], ['inverter' => ['count' => 2, 'kw' => 12], 'kwp' => 20, 'battery' => 16]);
        $this->assertSame(['inv-12', 2], $big['inverters']);
    }

    public function test_build_this_system_opens_build_your_own_prefilled(): void
    {
        $this->catalog();
        $html = $this->get('/pv-calculator?type=hybrid&mode=bill&bill=8000&rate=12.5&day=30')->assertOk()->assertSee('Build This System')->getContent();
        $this->assertSame(1, preg_match('#href="(/packages/build-your-own\?[^"]+)"#', $html, $m));
        $url = html_entity_decode($m[1]);
        $this->assertStringContainsString('inverter=', $url);
        $this->assertStringContainsString('kwp=', $url);

        $page = $this->get($url)->assertOk()->assertSee('From the PV Calculator:')->getContent();
        parse_str(parse_url($url, PHP_URL_QUERY), $q);
        // The matched inverter is the default choice, with the calculator's quantity.
        $this->assertMatchesRegularExpression('#name="quantity\[1\]"[^>]*value="'.$q['pq'].'"#', $page);
        $first = strpos($page, 'value="'.Product::query()->where('slug', $q['inverter'])->value('id').'"');
        $this->assertNotFalse($first);

        // Without a preset the page keeps its normal starting system.
        $this->get('/packages/build-your-own')->assertOk()->assertDontSee('From the PV Calculator:');
        // Bad values are ignored.
        $this->get('/packages/build-your-own?inverter=<x>&iq=abc')->assertOk()->assertDontSee('From the PV Calculator:');
    }
}
