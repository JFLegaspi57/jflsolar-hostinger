<?php

namespace Tests\Feature;

use App\Http\Controllers\MediaController;
use App\Models\MediaAsset;
use App\Models\Product;
use App\Models\User;
use App\Support\MediaStore;
use App\Support\PublicContent;
use App\Support\Ui;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ImageSpeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        PublicContent::flush();
    }

    private function photo(): MediaAsset
    {
        $product = Product::query()->where('status', 'published')->whereHas('category', fn ($q) => $q->where('status', 'published'))->firstOrFail();
        $key = 'products/'.$product->id.'/'.Str::uuid();
        @mkdir(MediaStore::path($key), 0775, true);
        $img = imagecreatetruecolor(8, 8);
        imagewebp($img, MediaStore::path($key, '640.webp'));

        return MediaAsset::query()->create(['kind' => 'image', 'storage_key' => $key, 'product_id' => $product->id, 'alt' => 'Test', 'widths' => [640], 'width' => 8, 'height' => 8]);
    }

    public function test_public_photos_are_cached_for_a_year_and_staff_copies_never(): void
    {
        $m = $this->photo();
        $res = $this->get('/media/'.$m->storage_key.'/640.webp')->assertOk();
        foreach (['public', 'max-age=31536000', 's-maxage=31536000', 'immutable'] as $part) {
            $this->assertStringContainsString($part, $res->headers->get('Cache-Control'));
        }
        $this->assertSame('public, max-age=31536000, s-maxage=31536000, immutable', MediaController::PUBLIC_CACHE);
        $this->assertSame([], $res->headers->getCookies(), 'Photos must not set cookies, or the CDN will not cache them');

        $staff = User::query()->create(['name' => 'Editor', 'email' => 'editor@jflsolar.test', 'password' => 'a long test password', 'role' => 'editor', 'active' => true, 'must_change_password' => false]);
        $admin = $this->actingAs($staff)->get('/admin/media/'.$m->storage_key.'/640.webp')->assertOk();
        $this->assertStringContainsString('no-store', $admin->headers->get('Cache-Control'));
        MediaStore::deleteFiles($m);
    }

    public function test_banners_offer_webp_with_jpeg_fallback(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('<source type="image/webp" srcset="/images/home-roof2-640.webp 640w', false)
            ->assertSee('src="/images/home-roof2-1280.jpg"', false);
        $this->get('/products')->assertOk()
            ->assertSee('srcset="/images/products-rooftop-640.webp 640w, /images/products-rooftop-960.webp 960w, /images/products-rooftop-1280.webp 1280w"', false);
        foreach (glob(public_path('images/*.jpg')) as $jpg) {
            $this->assertFileExists(substr($jpg, 0, -4).'.webp', 'Missing WebP twin for '.basename($jpg));
        }
        $this->assertNull(Ui::webpSrcset('/images/does-not-exist-640.jpg 640w'));
    }
}
