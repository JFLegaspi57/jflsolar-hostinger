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

    public function test_packages_menu_lists_complete_packages_and_materials_only(): void
    {
        $this->seed();
        $html = $this->get('/')->assertOk()->assertSee('aria-label="Package types"', false)->getContent();
        $this->assertStringContainsString('href="/packages?type=complete"', $html);
        $this->assertStringContainsString('>Complete Packages<', $html);
        $this->assertStringContainsString('href="/packages?type=materials"', $html);
        $this->assertStringContainsString('>Materials Only<', $html);
        // The open filter is marked in the menu.
        $this->assertMatchesRegularExpression('#href="/packages\\?type=materials"\\s+aria-current="page"\\s+class="nav-dd-link"#', $this->get('/packages?type=materials')->getContent());
    }
}
