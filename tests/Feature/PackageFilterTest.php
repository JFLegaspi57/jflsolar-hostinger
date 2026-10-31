<?php

namespace Tests\Feature;

use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_packages_and_materials_only_filters_and_old_link_redirects(): void
    {
        $this->seed();
        $installed = Package::query()->where('status', 'published')->where('package_type', 'installed_system')->firstOrFail();

        $this->get('/packages')->assertOk()->assertSee('Complete Packages')->assertSee('/packages?type=complete', false)->assertDontSee('Installed systems</a>', false);
        $this->get('/packages?type=complete')->assertOk()->assertSee('aria-current="page"', false)->assertSee($installed->name);
        $this->get('/packages?type=installed')->assertRedirect('/packages?type=complete')->assertStatus(301);
        $this->get('/')->assertDontSee('/packages?type=installed', false)->assertDontSee('/packages?type=equipment', false);

        $bundle = Package::query()->where('status', 'published')->where('package_type', 'equipment_bundle')->first();
        $this->get('/packages')->assertSee('Materials Only')->assertSee('/packages?type=materials', false)->assertDontSee('Equipment only</a>', false);
        $page = $this->get('/packages?type=materials')->assertOk()->assertDontSee($installed->name)->assertSee('Materials Only</h1>', false)->assertSee('A highly customizable bundle ideal for professional DIYers and professional installers.');
        $this->get('/packages')->assertSee('Solar packages</h1>', false);
        $this->get('/packages?type=complete')->assertSee('Complete packages</h1>', false)->assertSee('Everything done for you')->assertDontSee('Equipment-only bundles');
        $this->get('/packages')->assertSee('Three ways to go solar with us.')->assertSee('class="pkg-intro-link"', false)->assertDontSee('text-white underline', false)->assertSee('Sagot ka namin!')->assertDontSee('Equipment-only bundles')->assertDontSee('Installed systems</strong>', false);
        if ($bundle) {
            $page->assertSee($bundle->name);
        }
        $this->get('/packages?type=equipment')->assertRedirect('/packages?type=materials')->assertStatus(301);
    }
}
