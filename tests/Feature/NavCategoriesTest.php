<?php

namespace Tests\Feature;

use App\Models\ProductCategory;
use App\Support\PublicContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavCategoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_menu_lists_every_published_category(): void
    {
        $this->seed();
        PublicContent::flush();
        $published = ProductCategory::query()->where('status', 'published')->get();
        $this->assertNotEmpty($published);
        $html = $this->get('/')->assertOk()->assertSee('aria-label="Product categories"', false)->getContent();
        foreach ($published as $c) {
            $this->assertStringContainsString('href="/products?category='.$c->slug.'"', $html);
        }
        foreach (ProductCategory::query()->where('status', '!=', 'published')->get() as $c) {
            $this->assertStringNotContainsString('href="/products?category='.$c->slug.'"', $html);
        }
    }
}
