<?php

namespace Tests\Feature;

use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_packages_filter_and_old_link_redirect(): void
    {
        $this->seed();
        $installed = Package::query()->where('status', 'published')->where('package_type', 'installed_system')->firstOrFail();

        $this->get('/packages')->assertOk()->assertSee('Complete Packages')->assertSee('/packages?type=complete', false)->assertDontSee('Installed systems</a>', false);
        $this->get('/packages?type=complete')->assertOk()->assertSee('aria-current="page"', false)->assertSee($installed->name);
        $this->get('/packages?type=installed')->assertRedirect('/packages?type=complete')->assertStatus(301);
        $this->get('/')->assertDontSee('/packages?type=installed', false);
    }
}
