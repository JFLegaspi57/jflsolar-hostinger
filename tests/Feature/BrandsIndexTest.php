<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\BrandArticles;
use App\Support\PublicContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandsIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        PublicContent::flush();
    }

    private function product(string $cat, string $slug, string $brand): Product
    {
        $category = ProductCategory::query()->firstOrCreate(['slug' => $cat], ['name' => ucfirst(str_replace('-', ' ', $cat)), 'status' => 'published', 'sort_order' => 50]);

        return Product::query()->create(['slug' => $slug, 'name' => 'Item '.$slug, 'brand' => $brand, 'model' => 'M', 'category_id' => $category->id, 'summary' => '', 'description' => 'x', 'specs' => [],
            'pricing_mode' => 'fixed', 'price_php' => 100, 'price_basis' => 'Pickup price per piece', 'price_is_sample' => false, 'availability' => 'available', 'status' => 'published', 'is_sample' => false]);
    }

    public function test_every_shop_brand_is_listed_by_category_except_generic(): void
    {
        $this->product('inverters', 'gw-inv', 'GoodWe');
        $this->product('batteries', 'gw-bat', 'GoodWe');
        $this->product('solar-panels', 'trina-x', 'Trina Solar');
        $this->product('ac-safety-devices', 'chint-mcb', 'CHINT');
        $this->product('accessories', 'gen-x', 'Generic');
        PublicContent::flush();

        $html = $this->get('/brands')->assertOk()->assertSee('Our brands')->assertSee('CHINT')->assertSee('Trina Solar')->getContent();
        $this->assertStringNotContainsString('>Generic<', $html);
        $section = function (string $key) use ($html) {
            $s = substr($html, strpos($html, 'id="ob-'.$key.'"'));

            return substr($s, 0, strpos($s, '</section>'));
        };
        $this->assertStringContainsString('href="/brands/goodwe#lynx"', $section('batteries'));
        $this->assertStringContainsString('href="/brands/goodwe"', $section('inverters'));
        $this->assertStringContainsString('Tier 1 panel maker', $section('solar-panels'));
        $this->assertStringContainsString('/products/chint-mcb', $section('ac-safety-devices'));
        foreach (array_keys(BrandArticles::available()) as $slug) {
            $this->assertArrayHasKey($slug, BrandArticles::SUMMARIES, "No Our Brands summary for {$slug}");
        }
    }

    public function test_our_brands_is_in_the_about_menu_after_contact_and_in_the_sitemap(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $nav = substr($html, 0, strpos($html, '</nav>'));
        $this->assertGreaterThan(strpos($nav, 'href="/contact"'), strpos($nav, 'href="/brands"'));
        $this->assertLessThan(strpos($nav, 'href="/blog"'), strpos($nav, 'href="/brands"'));
        $this->get('/sitemap.xml')->assertSee('/brands<', false);
    }
}
