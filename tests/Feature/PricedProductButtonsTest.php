<?php

namespace Tests\Feature;

use App\Models\MediaAsset;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\PublicContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricedProductButtonsTest extends TestCase
{
    use RefreshDatabase;

    public function test_priced_products_show_only_add_to_cart_and_quote_only_products_keep_quote_and_call(): void
    {
        $this->seed();
        config(['shop.enabled' => true]);
        $category = ProductCategory::query()->where('status', 'published')->firstOrFail();
        $base = ['brand' => 'B', 'model' => 'M', 'category_id' => $category->id, 'summary' => '', 'description' => 'x', 'specs' => [], 'price_is_sample' => false, 'status' => 'published', 'is_sample' => false];
        Product::query()->create($base + ['slug' => 'priced-item', 'name' => 'Priced item', 'pricing_mode' => 'fixed', 'price_php' => 480, 'price_basis' => 'Pickup price per piece', 'availability' => 'available']);
        Product::query()->create($base + ['slug' => 'quote-item', 'name' => 'Quote item', 'pricing_mode' => 'quote', 'price_php' => null, 'price_basis' => '', 'availability' => 'on_request']);
        PublicContent::flush();

        $this->get('/products/priced-item')->assertOk()->assertSee('Add to cart')->assertDontSee('Request a quote')->assertDontSee('Request a Product Quote')
            ->assertDontSee('<x-icon name="phone"', false);
        $priced = $this->get('/products/priced-item')->getContent();
        $box = substr($priced, strpos($priced, 'Add to cart') - 3000, 3500);
        $this->assertStringNotContainsString('> Call</a>', $box);

        $this->get('/products/quote-item')->assertOk()->assertSee('Request a Product Quote')->assertSee('Call')->assertDontSee('Add to cart');
    }

    public function test_image_caption_shows_on_product_page_only_not_on_category_cards(): void
    {
        $this->seed();
        $category = ProductCategory::query()->where('status', 'published')->firstOrFail();
        $p = Product::query()->create(['brand' => 'B', 'model' => 'M', 'category_id' => $category->id, 'summary' => '', 'description' => 'x', 'specs' => [], 'price_is_sample' => false, 'status' => 'published', 'is_sample' => false,
            'slug' => 'temp-pic-item', 'name' => 'Temp pic item', 'pricing_mode' => 'fixed', 'price_php' => 150, 'price_basis' => 'Pickup price per piece', 'availability' => 'available']);
        MediaAsset::query()->create(['kind' => 'image', 'storage_key' => 'products/'.$p->id.'/00000000-0000-4000-8000-00000000abce', 'product_id' => $p->id, 'alt' => 'Clamp', 'caption' => 'Temporary picture — may differ from actual product appearance', 'widths' => [640], 'is_cover' => true]);
        PublicContent::flush();

        $this->get('/products/temp-pic-item')->assertOk()->assertSee('Temporary picture — may differ from actual product appearance');
        $this->get('/products')->assertOk()->assertSee('Temp pic item')->assertDontSee('Temporary picture');
    }
}
