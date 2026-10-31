<?php

namespace Tests\Feature;

use App\Models\ProductCategory;
use App\Support\PublicContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductsGroupedTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_products_are_grouped_by_category_in_menu_order(): void
    {
        $this->seed();
        PublicContent::flush();
        $cats = PublicContent::snapshot()['categories'];
        $withProducts = $cats->filter(fn ($c) => PublicContent::snapshot()['products']->contains('category_id', $c->id))->values();
        $this->assertGreaterThan(1, $withProducts->count());

        $html = $this->get('/products')->assertOk()->getContent();
        $positions = $withProducts->map(fn ($c) => strpos($html, 'id="cat-'.$c->slug.'"'))->all();
        $this->assertNotContains(false, $positions);
        $sorted = $positions;
        sort($sorted);
        $this->assertSame($sorted, $positions);   // same order as the menu
        $this->assertStringContainsString('<h4 class="mt-1', $html);   // product names sit under the group heading

        // A single category stays a plain list.
        $one = $withProducts->first();
        $this->get('/products?category='.$one->slug)->assertOk()->assertDontSee('id="cat-'.$one->slug.'"', false);
    }
}
