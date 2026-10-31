<?php

namespace Tests\Feature;

use App\Admin\FormParser;
use App\Admin\Values;
use App\Models\Package;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\PublicContent;
use App\Support\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class PackageBasketTest extends TestCase
{
    use RefreshDatabase;

    private function product(string $slug, string $name, float $price, string $availability = 'available'): Product
    {
        $category = ProductCategory::query()->where('status', 'published')->firstOrFail();

        return Product::query()->create(['slug' => $slug, 'name' => $name, 'brand' => 'B', 'model' => 'M', 'category_id' => $category->id, 'summary' => '', 'description' => 'x', 'specs' => [],
            'pricing_mode' => 'fixed', 'price_php' => $price, 'price_basis' => 'Pickup price per piece', 'price_is_sample' => false, 'availability' => $availability, 'status' => 'published', 'is_sample' => false]);
    }

    private function setUpPackage(): array
    {
        $this->seed();
        config(['shop.enabled' => true]);
        $inv = $this->product('t-inverter', 'Test Inverter 6kW', 40000);
        $panel = $this->product('t-panel', 'Test Panel 620W', 5900);
        $batA = $this->product('t-battery-a', 'Test Battery A', 106000);
        $batB = $this->product('t-battery-b', 'Test Battery B', 107000);
        $this->product('t-battery-c', 'Test Battery C', 99000, 'on_request');   // not sellable: never offered
        Package::query()->create([
            'slug' => 't-trio', 'name' => 'Test Trio', 'description' => '', 'package_type' => 'equipment_bundle', 'inclusions' => [], 'exclusions' => [], 'status' => 'published', 'is_sample' => false,
            'pricing_mode' => 'fixed', 'price_php' => 205000, 'price_basis' => 'Pickup price',
            'components' => [
                ['label' => 'Hybrid Inverter', 'detail' => '6 kW', 'quantity' => '1 unit', 'products' => ['t-inverter']],
                ['label' => 'Solar Panels', 'detail' => '620 W', 'quantity' => '10 pcs', 'products' => ['t-panel']],
                ['label' => 'Battery', 'detail' => '16 kWh', 'quantity' => '1 unit', 'products' => ['t-battery-a', 't-battery-c', 't-battery-b']],
                ['label' => 'Cables', 'detail' => 'Not sold online', 'quantity' => '1 lot'],
            ],
        ]);
        PublicContent::flush();

        return [$inv, $panel, $batA, $batB];
    }

    public function test_package_page_shows_each_part_price_and_the_total(): void
    {
        [$inv, $panel, $batA, $batB] = $this->setUpPackage();
        $this->get('/packages/t-trio')->assertOk()
            ->assertSee('Build your package')->assertSee('Add to Basket and Customize')->assertDontSee('Ask About This Package')->assertDontSee('<x-icon name="phone"', false)->assertDontSee('> Call ', false)
            ->assertSee('₱40,000')->assertSee('₱59,000')->assertSee('₱205,000')
            ->assertSee('Test Battery A')->assertSee('Test Battery B')->assertDontSee('Test Battery C')
            ->assertSee('name="quantity[1]"', false)->assertDontSee('name="quantity[3]"', false);
    }

    public function test_customized_parts_are_added_to_the_basket_separately(): void
    {
        [$inv, $panel, $batA, $batB] = $this->setUpPackage();
        $this->post('/packages/t-trio/basket', ['product' => [0 => $inv->id, 1 => $panel->id, 2 => $batB->id], 'quantity' => [0 => 0, 1 => 12, 2 => 1]])
            ->assertRedirect('/packages/t-trio#build')->assertSessionHas('pkg_added');
        $this->assertSame([$panel->id => 12, $batB->id => 1], Shop::raw());

        // A product that is not one of the choices is refused; nothing more is added.
        $this->post('/packages/t-trio/basket', ['product' => [2 => 'not-a-choice'], 'quantity' => [0 => 0, 1 => 0, 2 => 1]])->assertSessionHas('pkg_error');
        $this->post('/packages/t-trio/basket', ['quantity' => [0 => 0, 1 => 0, 2 => 0]])->assertSessionHas('pkg_error');
        $this->assertSame([$panel->id => 12, $batB->id => 1], Shop::raw());
    }

    public function test_packages_without_linked_products_keep_the_inquiry_buttons_only(): void
    {
        $this->seed();
        config(['shop.enabled' => true]);
        $pkg = Package::query()->where('status', 'published')->firstOrFail();
        $pkg->update(['components' => [['label' => 'Inverter', 'detail' => 'x', 'quantity' => '1']]]);
        PublicContent::flush();
        $this->get('/packages/'.$pkg->slug)->assertOk()->assertDontSee('Build your package')->assertSee('Ask About This Package');
        $this->post('/packages/'.$pkg->slug.'/basket', ['quantity' => [0 => 1]])->assertSessionHas('pkg_error');
    }

    public function test_admin_components_field_reads_and_writes_product_links(): void
    {
        $parser = new FormParser(Request::create('/', 'POST', ['components' => "Battery | 16 kWh | 1 unit | dyness-a, https://www.jflsolar.ph/products/ate-b\nPanels | 620 W | 10 pcs"]));
        $rows = $parser->components('components');
        $this->assertTrue($parser->ok());
        $this->assertSame(['dyness-a', 'ate-b'], $rows[0]['products']);
        $this->assertArrayNotHasKey('products', $rows[1]);

        $bad = new FormParser(Request::create('/', 'POST', ['components' => 'Battery | 16 kWh | 1 | not a slug!']));
        $bad->components('components');
        $this->assertFalse($bad->ok());

        $pkg = new Package(['components' => [['label' => 'Battery', 'detail' => '16 kWh', 'quantity' => null, 'products' => ['dyness-a']], ['label' => 'Panels', 'detail' => '620 W', 'quantity' => '10 pcs']]]);
        $this->assertSame("Battery | 16 kWh |  | dyness-a\nPanels | 620 W | 10 pcs", Values::package($pkg)['components']);
    }

    public function test_optional_add_ons_from_the_accessory_categories(): void
    {
        [$inv, $panel, $batA, $batB] = $this->setUpPackage();
        $cat = fn (string $slug, string $name) => ProductCategory::query()->firstOrCreate(['slug' => $slug], ['name' => $name, 'status' => 'published', 'sort_order' => 90]);
        $acc = $cat('accessories', 'Accessories');
        $dc = $cat('dc-safety-devices', 'DC Safety Devices');
        $mc4 = Product::query()->create(['slug' => 't-mc4', 'name' => 'Test MC4 Pair', 'brand' => 'Generic', 'model' => '', 'category_id' => $acc->id, 'summary' => '', 'description' => 'x', 'specs' => [],
            'pricing_mode' => 'fixed', 'price_php' => 65, 'price_basis' => 'Pickup price per pair', 'price_is_sample' => false, 'availability' => 'available', 'status' => 'published', 'is_sample' => false]);
        $spd = Product::query()->create(['slug' => 't-spd', 'name' => 'Test DC SPD', 'brand' => '', 'model' => '', 'category_id' => $dc->id, 'summary' => '', 'description' => 'x', 'specs' => [],
            'pricing_mode' => 'quote', 'price_php' => null, 'price_basis' => '', 'price_is_sample' => false, 'availability' => 'available', 'status' => 'published', 'is_sample' => false]);
        PublicContent::flush();

        $this->get('/packages/t-trio')->assertOk()->assertSee('Add-ons')->assertSee('Test MC4 Pair')->assertSee('name="addon['.$mc4->id.']"', false)
            ->assertDontSee('Test DC SPD');   // no fixed price: not offered

        // Add-ons alone are enough; unknown or unsellable ids are ignored.
        $this->post('/packages/t-trio/basket', ['quantity' => [0 => 0, 1 => 0, 2 => 0], 'addon' => [$mc4->id => 4, $spd->id => 2, $inv->id => 3, 'nope' => 1]])->assertSessionHas('pkg_added');
        $this->assertSame([$mc4->id => 4], Shop::raw());
    }
}
