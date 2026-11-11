<?php

namespace Tests\Feature;

use App\Models\Product;
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
            ->assertSee('Who is GoodWe?')->assertSee('GoodWe in the Philippines')->assertSee('GW6000-ES-C10')
            ->assertSee('href="/products?brand=GoodWe#results"', false);
        if ($goodwe) {
            $res->assertSee('href="/products/'.$goodwe->slug.'"', false);
        }
        $this->get('/sitemap.xml')->assertOk()->assertSee('/brands/goodwe');
    }

    public function test_goodwe_logo_opens_the_article_and_other_logos_filter_products(): void
    {
        $this->get('/products')->assertOk()
            ->assertSee('href="/brands/goodwe" class="brand-logo" title="About GoodWe"', false)
            ->assertSee('href="/products?brand=Jinko+Solar#results" class="brand-logo"', false)
            ->assertDontSee('href="/products?brand=GoodWe#results" class="brand-logo"', false);
    }
}
