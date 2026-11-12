<?php

namespace Tests\Feature;

use App\Models\ContentBlock;
use App\Models\MediaAsset;
use App\Models\User;
use App\Support\PublicContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BrandPosterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        PublicContent::flush();
    }

    private function staff(): User
    {
        return User::query()->create(['name' => 'Editor', 'email' => 'editor@jflsolar.test', 'password' => 'a long test password', 'role' => 'editor', 'active' => true, 'must_change_password' => false]);
    }

    public function test_brand_pages_have_no_banner_until_a_poster_is_added(): void
    {
        $this->get('/brands/goodwe')->assertOk()->assertDontSee('class="ba-poster"', false);
    }

    public function test_staff_add_a_poster_banner_to_a_brand_page(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff, 'web')->get('/admin/content/new')->assertOk()->assertSee('Brand page banner');
        $this->actingAs($staff, 'web')->post('/admin/content', [
            'section' => 'brand', 'key' => 'brand.goodwe', 'title' => 'GoodWe banner', 'published' => '1', 'confirmed' => '1',
        ])->assertRedirect('/admin/content/brand.goodwe');
        $this->actingAs($staff, 'web')->get('/admin/content/brand.goodwe')->assertOk()
            ->assertSee('GoodWe brand page (/brands/goodwe) — banner poster')->assertSee('Banner poster');
        $this->actingAs($staff, 'web')->post('/admin/media/upload/block/brand.goodwe', ['alt' => 'GoodWe home battery product poster', 'file' => UploadedFile::fake()->image('poster.jpg', 1920, 950)]);
        $img = MediaAsset::query()->where('content_block_key', 'brand.goodwe')->firstOrFail();
        $this->actingAs($staff, 'web')->post('/admin/media/'.$img->id.'/text', ['alt' => $img->alt, 'caption' => 'Poster: GoodWe']);
        auth('web')->logout();
        PublicContent::flush();

        $this->get('/brands/goodwe')->assertOk()
            ->assertSee('class="ba-poster"', false)->assertSee('alt="GoodWe home battery product poster"', false)
            ->assertSee('Poster: GoodWe')->assertSee('draggable="false"', false)
            ->assertSeeInOrder(['class="ba-poster"', '<h1'], false);
        // Only that brand gets it; other pages are unchanged.
        $this->get('/brands/solis')->assertOk()->assertDontSee('class="ba-poster"', false);
        $this->get('/about')->assertOk()->assertDontSee('GoodWe banner');

        // Hidden block → no banner.
        ContentBlock::query()->whereKey('brand.goodwe')->update(['published' => false]);
        PublicContent::flush();
        $this->get('/brands/goodwe')->assertOk()->assertDontSee('class="ba-poster"', false);
    }
}
