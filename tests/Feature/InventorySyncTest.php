<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Support\InventorySync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventorySyncTest extends TestCase
{
    use RefreshDatabase;

    private function product(string $slug, string $availability): Product
    {
        $category = ProductCategory::query()->firstOrCreate(['slug' => 'parts'], ['name' => 'Parts', 'status' => 'published', 'sort_order' => 50]);

        return Product::query()->create(['slug' => $slug, 'name' => $slug, 'brand' => 'B', 'model' => 'M', 'category_id' => $category->id, 'summary' => '', 'description' => 'x', 'specs' => [],
            'pricing_mode' => 'fixed', 'price_php' => 100, 'price_basis' => 'Pickup price', 'price_is_sample' => false, 'availability' => $availability, 'status' => 'published', 'is_sample' => false]);
    }

    private function owner(): User
    {
        return User::query()->create(['name' => 'Owner', 'email' => 'owner@jflsolar.test', 'password' => 'a long test password', 'role' => 'owner', 'active' => true, 'must_change_password' => false]);
    }

    public function test_sheet_quantities_set_website_availability(): void
    {
        $goodwe = $this->product('goodwe-gw6000-es-c10-hybrid-inverter', 'on_request');
        $lux = $this->product('luxpower-gen2-lb-eu-12k-hybrid-inverter', 'available');
        $clamp = $this->product('end-clamp', 'on_request');
        $dyness = $this->product('dyness-314ah-powerbrick-sc', 'preorder');
        $trina = $this->product('trina-solar-panels', 'available');

        $this->actingAs($this->owner(), 'web');
        $this->post('/admin/inventory/connect')->assertRedirect('/admin/inventory');
        $script = session('inventory_script');
        $this->assertMatchesRegularExpression("/JFL_SECRET = '([0-9a-f]{48})'/", $script);
        preg_match("/JFL_SECRET = '([0-9a-f]{48})'/", $script, $m);
        $this->assertStringNotContainsString($m[1], json_encode(\DB::table('app_secrets')->get()));

        $items = ['sheet' => 'Nov 2026', 'items' => [
            ['name' => 'GoodWe 6kW Hybrid Inverter', 'qty' => 18],
            ['name' => 'LuxPower Hybrid Inverter - 12KW (LXP-LB-EU-12K) Gen2', 'qty' => -1],
            ['name' => 'End Clamp', 'qty' => 0],
            ['name' => 'End  Clamp', 'qty' => 800],      // second row, extra space: summed
            ['name' => 'Dyness 314AH Powerbrick SC (IP20)', 'qty' => 0],
            ['name' => 'Deye 6kW hybrid Inverter', 'qty' => 0],
        ]];
        $this->postJson('/webhooks/inventory', $items)->assertStatus(401);
        $this->postJson('/webhooks/inventory', $items, ['Authorization' => 'Bearer wrong'])->assertStatus(401);
        $this->postJson('/webhooks/inventory', $items, ['Authorization' => 'Bearer '.$m[1]])->assertOk()->assertJson(['ok' => true, 'linked' => 4, 'changed' => 3, 'unmatched' => 1]);

        $this->assertSame('available', $goodwe->fresh()->availability);
        $this->assertSame('on_request', $lux->fresh()->availability);
        $this->assertSame('available', $clamp->fresh()->availability);
        $this->assertSame('preorder', $dyness->fresh()->availability);   // pre-order kept at 0 stock
        $this->assertSame('available', $trina->fresh()->availability);   // not in the sheet: untouched

        $this->get('/admin/inventory')->assertOk()->assertSee('linked')->assertSee('Nov 2026')
            ->assertSee('Ask about availability → ', false)->assertSee('Deye 6kW hybrid Inverter')->assertDontSee($m[1]);

        // Link the unmatched item, then it updates the product at the next sync.
        $deye = $this->product('deye-6kw', 'available');
        $this->post('/admin/inventory/link', ['name' => 'Deye 6kW hybrid Inverter', 'slug' => 'deye-6kw'])->assertRedirect('/admin/inventory');
        $this->postJson('/webhooks/inventory', $items, ['Authorization' => 'Bearer '.$m[1]])->assertOk()->assertJson(['unmatched' => 0]);
        $this->assertSame('on_request', $deye->fresh()->availability);

        $this->post('/admin/inventory/disconnect');
        $this->postJson('/webhooks/inventory', $items, ['Authorization' => 'Bearer '.$m[1]])->assertStatus(401);
    }

    public function test_script_reads_the_newest_month_tab_and_sends_no_prices(): void
    {
        $script = InventorySync::script('abc');
        $this->assertStringContainsString('/webhooks/inventory', $script);
        $this->assertStringContainsString('on-?\s*hand', $script);
        $this->assertStringNotContainsString('price', strtolower(str_replace('no prices', '', $script)));
    }
}
