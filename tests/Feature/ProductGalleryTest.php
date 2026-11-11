<?php

namespace Tests\Feature;

use App\Models\MediaAsset;
use App\Models\Product;
use App\Support\MediaStore;
use App\Support\PublicContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductGalleryTest extends TestCase
{
    use RefreshDatabase;

    private function image(Product $product, string $alt, int $order): MediaAsset
    {
        $key = 'products/'.$product->id.'/'.Str::uuid();
        @mkdir(MediaStore::path($key), 0775, true);
        imagewebp(imagecreatetruecolor(8, 8), MediaStore::path($key, '640.webp'));

        return MediaAsset::query()->create(['kind' => 'image', 'storage_key' => $key, 'product_id' => $product->id, 'alt' => $alt, 'widths' => [640], 'width' => 8, 'height' => 8, 'sort_order' => $order]);
    }

    public function test_hovering_a_thumbnail_can_show_every_photo_including_the_first(): void
    {
        $this->seed();
        $product = Product::query()->where('status', 'published')->whereHas('category', fn ($q) => $q->where('status', 'published'))->firstOrFail();
        MediaAsset::query()->where('product_id', $product->id)->where('kind', 'image')->delete();
        $a = $this->image($product, 'Front view', 1);
        $b = $this->image($product, 'Side view', 2);
        PublicContent::flush();

        $html = $this->get('/products/'.$product->slug)->assertOk()
            ->assertSee('data-gallery-main', false)
            ->assertSee('aria-label="Show image 1: Front view"', false)
            ->assertSee('aria-label="Show image 2: Side view"', false)
            ->assertSee('aria-pressed="true"', false)
            ->getContent();
        $this->assertSame(2, substr_count($html, 'data-gallery-thumb '));
        $this->assertSame(1, substr_count($html, "addEventListener('mouseenter'"));
        MediaStore::deleteFiles($a);
        MediaStore::deleteFiles($b);
    }

    public function test_a_single_photo_has_no_thumbnails(): void
    {
        $this->seed();
        $product = Product::query()->where('status', 'published')->whereHas('category', fn ($q) => $q->where('status', 'published'))->firstOrFail();
        MediaAsset::query()->where('product_id', $product->id)->where('kind', 'image')->delete();
        $a = $this->image($product, 'Front view', 1);
        PublicContent::flush();
        $this->get('/products/'.$product->slug)->assertOk()->assertDontSee('data-gallery-thumb', false)->assertDontSee('data-gallery-main', false);
        MediaStore::deleteFiles($a);
    }
}
