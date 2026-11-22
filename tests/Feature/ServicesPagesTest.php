<?php

namespace Tests\Feature;

use App\Support\PublicContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServicesPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        PublicContent::flush();
    }

    public function test_services_menu_lists_the_service_pages(): void
    {
        $this->get('/')->assertOk()->assertSeeInOrder([
            'href="/services"', 'All Services', 'href="/projects"', 'Installation', 'href="/products"', 'Supply',
            'href="/services/consultation"', 'Consultation', 'href="/services/training-seminars"', 'Training &amp; Seminars',
            'href="/services/sld-block-diagram"', 'SLD &amp; Block Diagram',
        ], false);
    }

    public function test_service_pages_open_and_highlight_services(): void
    {
        $this->get('/services/consultation')->assertOk()->assertSee('Solar PV Consultation')->assertSee('Second opinion on a quotation')
            ->assertSee('aria-current="page"', false);
        $this->get('/services/training-seminars')->assertOk()->assertSee('Training &amp; Seminars', false)->assertSee('Learn from the best')
            ->assertSee('href="/courses/solar-pv-system-design"', false);
        $this->get('/services/sld-block-diagram')->assertOk()->assertSee('SLD &amp; Block Diagram', false)->assertSee('Single-line diagram (SLD)')
            ->assertSee('/images/service-sld-sample-1600.jpg', false);
        $this->get('/sitemap.xml')->assertSee('/services/consultation')->assertSee('/services/sld-block-diagram');
    }
}
