<?php

namespace Tests\Feature;

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

    public function test_our_brands_lists_every_brand_article_by_group(): void
    {
        $res = $this->get('/brands')->assertOk()->assertSee('Our brands')
            ->assertSeeInOrder(['Inverters', 'Solar panels', 'Batteries', 'Safety and protection']);
        foreach (BrandArticles::available() as $slug => $a) {
            $this->assertArrayHasKey($slug, BrandArticles::OVERVIEW, "No Our Brands summary for {$slug}");
            $res->assertSee('href="/brands/'.$slug.'"', false)->assertSee($a['brand']);
        }
    }

    public function test_goodwe_batteries_and_trina_panels_are_listed(): void
    {
        $cat = \App\Models\ProductCategory::query()->firstOrCreate(['slug' => 'solar-panels'], ['name' => 'Solar panels', 'status' => 'published', 'sort_order' => 1]);
        \App\Models\Product::query()->create(['slug' => 'trina-x', 'name' => 'Trina panel', 'brand' => 'Trina Solar', 'model' => 'M', 'category_id' => $cat->id, 'summary' => '', 'description' => 'x', 'specs' => [],
            'pricing_mode' => 'fixed', 'price_php' => 100, 'price_basis' => 'Pickup price per piece', 'price_is_sample' => false, 'availability' => 'available', 'status' => 'published', 'is_sample' => false]);
        PublicContent::flush();
        $html = $this->get('/brands')->assertOk()->getContent();
        $section = function (string $key) use ($html) {
            $s = substr($html, strpos($html, 'id="ob-'.$key.'"'));

            return substr($s, 0, strpos($s, '</section>'));
        };
        $this->assertStringContainsString('href="/brands/goodwe#lynx"', $section('batteries'));
        $this->assertStringContainsString('Trina Solar', $section('panels'));
        $this->assertStringContainsString('Tier 1 panel maker', $section('panels'));
        $this->assertStringNotContainsString('CHINT', $html);
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
