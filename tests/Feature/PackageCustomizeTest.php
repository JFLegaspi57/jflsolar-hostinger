<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\PublicContent;
use App\Support\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageCustomizeTest extends TestCase
{
    use RefreshDatabase;

    private function product(string $slug, string $name, float $price): Product
    {
        $category = ProductCategory::query()->where('status', 'published')->firstOrFail();

        return Product::query()->create(['slug' => $slug, 'name' => $name, 'brand' => 'B', 'model' => 'M', 'category_id' => $category->id, 'summary' => '', 'description' => 'x', 'specs' => [],
            'pricing_mode' => 'fixed', 'price_php' => $price, 'price_basis' => 'Pickup price per piece', 'price_is_sample' => false, 'availability' => 'available', 'status' => 'published', 'is_sample' => false]);
    }

    private function setUpPackage(): void
    {
        $this->seed();
        config(['shop.enabled' => true]);
        $this->product('t-panel', 'Test Panel 650W', 6300);
        $this->product('t-battery', 'Test Battery 16kWh', 107000);
        Package::query()->create([
            'slug' => 't-12kw', 'name' => 'Test 12kW Hybrid', 'description' => '', 'package_type' => 'installed_system', 'inclusions' => [], 'exclusions' => [], 'status' => 'published', 'is_sample' => false,
            'pricing_mode' => 'starting', 'price_php' => 890800, 'price_basis' => 'Installed',
            'components' => [
                ['label' => 'Hybrid inverter', 'detail' => '12kW', 'quantity' => '1'],
                ['label' => 'Solar panels', 'detail' => '650W', 'quantity' => '27', 'products' => ['t-panel']],
                ['label' => 'Battery', 'detail' => '16kWh', 'quantity' => '3', 'products' => ['t-battery']],
            ],
        ]);
        PublicContent::flush();
    }

    public function test_installed_packages_can_be_customized_but_not_bought_online(): void
    {
        $this->setUpPackage();
        $this->get('/packages/t-12kw')->assertOk()
            ->assertSee('Customize this package')->assertSee('Customize This Package')->assertSee('Ask About This Package')
            ->assertSee('name="q[1]"', false)->assertSee('name="q[2]"', false)->assertDontSee('name="q[0]"', false)
            ->assertDontSee('Add to Basket');
        $this->post('/packages/t-12kw/basket', ['quantity' => [1 => 1]])->assertSessionHas('pkg_error');
        $this->assertSame([], Shop::raw());
    }

    public function test_the_inquiry_describes_the_customized_setup_and_its_estimated_price(): void
    {
        $this->setUpPackage();
        // 20 panels instead of 27 and 2 batteries instead of 3: 890,800 − 7 × 6,300 − 107,000.
        $this->get('/contact?package=t-12kw&custom=1&q[1]=20&q[2]=2&p[1]=t-panel')->assertOk()
            ->assertSee('Customized Test 12kW Hybrid:')->assertSee('20 × B Test Panel 650W (package has 27)')
            ->assertSee('2 × B Test Battery 16kWh (package has 3)')->assertSee('Estimated price: ₱739,700');
        // Without "custom" the message stays empty as before.
        $this->get('/contact?package=t-12kw')->assertOk()->assertDontSee('Customized Test 12kW Hybrid:');
    }

    public function test_packages_that_show_no_price_are_not_customizable(): void
    {
        $this->setUpPackage();
        Package::query()->where('slug', 't-12kw')->update(['pricing_mode' => 'quote', 'price_php' => null]);
        PublicContent::flush();
        $this->get('/packages/t-12kw')->assertOk()->assertDontSee('Customize this package');
    }
}
