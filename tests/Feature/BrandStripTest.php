<?php

namespace Tests\Feature;

use App\Support\PublicContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandStripTest extends TestCase
{
    use RefreshDatabase;

    public function test_brand_logos_show_on_all_products_but_not_in_a_category(): void
    {
        $this->seed();
        PublicContent::flush();
        $slug = PublicContent::snapshot()['categories']->first()->slug;
        $this->get('/products')->assertOk()->assertSee('aria-label="Brands we carry"', false);
        $this->get('/products?brand=GoodWe')->assertOk()->assertSee('aria-label="Brands we carry"', false);
        $this->get('/products?category='.$slug)->assertOk()->assertDontSee('aria-label="Brands we carry"', false)->assertDontSee('brand-logos-v3', false);
    }
}
