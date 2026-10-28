<?php

namespace Tests\Feature;

use App\Models\ContentBlock;
use App\Models\MediaAsset;
use App\Models\Package;
use App\Support\MediaStore;
use App\Support\PublicContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        PublicContent::flush();
    }

    private function production(bool $on): void
    {
        config(['site.env' => $on ? 'production' : 'preview']);
        PublicContent::flush();
    }

    public function test_every_public_page_renders(): void
    {
        foreach (['/', '/services', '/about', '/packages', '/products', '/projects', '/contact', '/privacy', '/terms', '/robots.txt', '/sitemap.xml'] as $path) {
            $this->get($path)->assertOk();
        }
        $this->get('/packages/does-not-exist')->assertNotFound();
    }

    public function test_preview_shows_labelled_samples_and_blocks_robots(): void
    {
        $this->production(false);
        $this->get('/packages')->assertSee('Proposal preview')->assertSee('Sample');
        $this->get('/robots.txt')->assertSee('Disallow: /');
        $this->get('/')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_live_mode_with_indexing_off_hides_ribbon_but_blocks_robots(): void
    {
        $this->production(true);
        config(['site.indexing' => false]);
        $this->get('/packages')->assertOk()->assertDontSee('Proposal preview')->assertSee('noindex, nofollow', false);
        $this->get('/robots.txt')->assertSee('Disallow: /');
        $this->get('/')->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        config(['site.indexing' => true]);
        $this->get('/robots.txt')->assertSee('Disallow: /admin')->assertSee('Sitemap:');
        $this->get('/')->assertHeaderMissing('X-Robots-Tag');
    }

    public function test_production_hides_samples_unconfirmed_claims_and_drafts(): void
    {
        $this->production(true);
        $sample = Package::query()->where('is_sample', true)->where('status', 'published')->first();
        $this->get('/packages/'.$sample->slug)->assertNotFound();
        $this->get('/packages')->assertDontSee('Proposal preview');

        $real = Package::query()->create([
            'slug' => 'real-package', 'name' => 'Real package', 'description' => '', 'package_type' => 'installed_system',
            'components' => [], 'inclusions' => [], 'exclusions' => [], 'status' => 'published', 'is_sample' => false,
        ]);
        PublicContent::flush();
        $this->get('/packages/real-package')->assertOk();
        $real->update(['status' => 'draft']);
        PublicContent::flush();
        $this->get('/packages/real-package')->assertNotFound();

        $block = ContentBlock::query()->where('claim_status', 'needs_confirmation')->where('published', true)->first();
        $this->get('/about')->assertDontSee($block->title);
    }

    public function test_media_follows_the_owning_items_visibility(): void
    {
        $pkg = Package::query()->create([
            'slug' => 'media-test', 'name' => 'Media test', 'description' => '', 'package_type' => 'installed_system',
            'components' => [], 'inclusions' => [], 'exclusions' => [], 'status' => 'draft', 'is_sample' => false,
        ]);
        $key = 'packages/'.$pkg->id.'/00000000-0000-4000-8000-00000000abcd';
        @mkdir(MediaStore::path($key), 0775, true);
        file_put_contents(MediaStore::path($key, '640.webp'), 'RIFF');
        MediaAsset::query()->create(['kind' => 'image', 'storage_key' => $key, 'package_id' => $pkg->id, 'alt' => 'Test', 'widths' => [640]]);

        $this->get("/media/{$key}/640.webp")->assertNotFound(); // draft
        $pkg->update(['status' => 'published']);
        $this->get("/media/{$key}/640.webp")->assertOk()->assertHeader('Content-Type', 'image/webp');
        $this->get("/media/{$key}/1280.webp")->assertNotFound(); // not a stored width
        $this->get('/admin/media/'.$key.'/640.webp')->assertRedirect('/admin/login');

        MediaStore::deleteFiles(MediaAsset::query()->where('storage_key', $key)->first());
    }

    public function test_security_headers_are_sent(): void
    {
        $this->get('/')->assertHeader('X-Frame-Options', 'DENY')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get('/admin/login')->assertHeader('Cache-Control')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_admin_requires_sign_in(): void
    {
        foreach (['/admin', '/admin/packages', '/admin/staff', '/admin/inquiries', '/admin/settings'] as $path) {
            $this->get($path)->assertRedirect('/admin/login');
        }
        // (CSRF checks are skipped by Laravel in tests; the browser test covers real forms.)
        $this->post('/admin/packages', ['name' => 'x'])->assertRedirect('/admin/login');
        $this->assertDatabaseMissing('packages', ['name' => 'x']);
    }
}
