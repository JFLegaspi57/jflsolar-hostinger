<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Support\PublicContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackagePhotoLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_package_card_photo_opens_the_package(): void
    {
        $this->seed();
        PublicContent::flush();
        $pkg = Package::query()->where('status', 'published')->where('package_type', 'materials_only')->first()
            ?? Package::query()->where('status', 'published')->firstOrFail();
        $html = $this->get('/packages?type=materials')->assertOk()->getContent();
        if (str_contains($html, 'pkg-photo')) {
            $this->assertMatchesRegularExpression('#<a href="/packages/[a-z0-9-]+" tabindex="-1" aria-hidden="true" class="pkg-photo#', $html);
        } else {
            $this->markTestSkipped('No package cards with photos in the seed data.');
        }
    }
}
