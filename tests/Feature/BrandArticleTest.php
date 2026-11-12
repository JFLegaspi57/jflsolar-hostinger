<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Support\BrandArticles;
use App\Support\PublicContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandArticleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        PublicContent::flush();
    }

    public function test_goodwe_article_shows_the_story_and_our_goodwe_products(): void
    {
        $goodwe = Product::query()->where('brand', 'GoodWe')->where('status', 'published')->first();
        $res = $this->get('/brands/goodwe')->assertOk()
            ->assertSee('<h1', false)->assertSee('GoodWe inverters and batteries')
            ->assertSee('Who is GoodWe?')->assertSee('GoodWe in the Philippines')->assertSee('GW6000-ES-C10');
        if ($goodwe) {
            $res->assertSee('href="/products/'.$goodwe->slug.'"', false)->assertSee('href="/products?brand=GoodWe#results"', false);
        }
        $this->get('/sitemap.xml')->assertOk()->assertSee('/brands/goodwe')->assertSee('/brands/jinko-solar');
    }

    public function test_every_written_article_renders_and_links_to_the_others(): void
    {
        $available = BrandArticles::available();
        $this->assertArrayHasKey('goodwe', $available);
        $this->assertArrayHasKey('jinko-solar', $available);
        foreach ($available as $slug => $a) {
            $this->assertFileExists(public_path('images/brand-logo-'.$slug.'.webp'));
            $res = $this->get('/brands/'.$slug)->assertOk()->assertSeeInOrder(['class="ba-logo"', 'Brand guide', 'src="data:image/webp;base64,', 'alt="'.$a['brand'].' logo"', '<h1 class="ba-logo-title">'], false)
                ->assertSee($a['brand'].' products we sell')->assertSee('Request a Quote')->assertSee('Sources:');
            foreach (array_keys($available) as $other) {
                if ($other !== $slug) {
                    $res->assertSee('href="/brands/'.$other.'"', false);
                }
            }
        }
    }

    public function test_unwritten_articles_are_not_found_or_linked(): void
    {
        foreach (array_diff(array_keys(BrandArticles::ARTICLES), array_keys(BrandArticles::available())) as $slug) {
            $this->get('/brands/'.$slug)->assertNotFound();
            $this->get('/products')->assertDontSee('href="/brands/'.$slug.'"', false);
        }
        $this->get('/brands/not-a-brand')->assertNotFound();
    }

    public function test_logos_open_their_article_or_else_the_brand_products(): void
    {
        $res = $this->get('/products')->assertOk()
            ->assertSee('href="/brands/goodwe" class="brand-logo" title="About GoodWe"', false)
            ->assertSee('href="/brands/jinko-solar" class="brand-logo" title="About Jinko Solar"', false)
            ->assertDontSee('href="/products?brand=GoodWe#results" class="brand-logo"', false);
        if (! isset(BrandArticles::available()['sungrow'])) {
            $res->assertSee('href="/products?brand=Sungrow#results" class="brand-logo"', false);
        }
    }

    public function test_inverter_and_panel_brands_show_their_tier_classification(): void
    {
        $this->get('/brands/goodwe')->assertOk()->assertSee('Tier classification')->assertSee('BloombergNEF Tier 1 PV inverter manufacturer');
        $this->get('/brands/jinko-solar')->assertOk()->assertSee('Tier 1 panel maker');
        $this->get('/brands/luxpower')->assertOk()->assertDontSee('Tier classification');
        $this->get('/brands/seraphim')->assertOk()->assertDontSee('Tier classification');
        $this->get('/brands/dyness')->assertOk()->assertDontSee('Tier classification');
    }
}
