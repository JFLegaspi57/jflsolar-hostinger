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
        $this->assertStringNotContainsString('nav-dd-link font-semibold">All products<', $html);   // the Products link itself opens the full list
        foreach ($published as $c) {
            $this->assertStringContainsString('href="/products?category='.$c->slug.'#categories"', $html);
        }
        foreach (ProductCategory::query()->where('status', '!=', 'published')->get() as $c) {
            $this->assertStringNotContainsString('href="/products?category='.$c->slug.'#categories"', $html);
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
        $this->assertStringNotContainsString('nav-dd-link font-semibold">All packages<', $html);
        // Order: Materials Only, Build Your Own, Complete Packages (menu and the Packages page buttons).
        $menu = substr($html, strpos($html, 'aria-label="Package types"'));
        $this->assertTrue(strpos($menu, '>Materials Only<') < strpos($menu, 'Build Your Own<') && strpos($menu, 'Build Your Own<') < strpos($menu, '>Complete Packages<'));
        $page = $this->get('/packages')->getContent();
        $buttons = substr($page, strpos($page, 'aria-label="Package type"'));
        $this->assertTrue(strpos($buttons, '>All packages<') < strpos($buttons, '>Materials Only<') && strpos($buttons, '>Materials Only<') < strpos($buttons, 'Build Your Own') && strpos($buttons, 'Build Your Own') < strpos($buttons, '>Complete Packages<'));   // the Packages link itself opens the full list
        // The open filter is marked in the menu.
        $this->assertMatchesRegularExpression('#href="/packages\\?type=materials"\\s+aria-current="page"\\s+class="nav-dd-link"#', $this->get('/packages?type=materials')->getContent());
    }
}
