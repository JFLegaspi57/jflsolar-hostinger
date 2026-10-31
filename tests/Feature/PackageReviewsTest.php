<?php

namespace Tests\Feature;

use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageReviewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_individual_package_pages_do_not_show_customer_reviews(): void
    {
        $this->seed();
        $pkg = Package::query()->where('status', 'published')->firstOrFail();

        // Other pages (e.g. About) keep the reviews strip above the footer; the packages list already hides it.
        $this->get('/about')->assertOk()->assertSee('What people say about us');
        $this->get('/packages/'.$pkg->slug)->assertOk()->assertSee($pkg->name)->assertDontSee('What people say about us');
    }
}
