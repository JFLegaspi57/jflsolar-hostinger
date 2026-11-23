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
            ->assertSee('aria-current="page"', false)->assertDontSee('id="reviews-title"', false);
        $this->get('/services/training-seminars')->assertOk()->assertSee('Training &amp; Seminars', false)->assertSee('Learn from the best')
            ->assertSee('href="/courses/solar-pv-system-design"', false)->assertSee('From our trainings and seminars')->assertSee('JFL Solar Training Center')->assertSee('/images/training-photo-01-640.jpg', false);
        $this->get('/services/sld-block-diagram')->assertOk()->assertSee('SLD &amp; Block Diagram', false)->assertSee('Single-line diagram (SLD)')
            ->assertSee('/images/service-sld-plan-1280.jpg', false)->assertDontSee('service-sld-sample', false)->assertDontSee('id="reviews-title"', false);
        $this->get('/sitemap.xml')->assertSee('/services/consultation')->assertSee('/services/sld-block-diagram');
    }

    public function test_seminar_page_and_upcoming_card(): void
    {
        $this->travelTo(\Carbon\CarbonImmutable::parse('2026-11-23 10:00', 'Asia/Manila'));
        $url = '/services/training-seminars/solar-pv-installation-seminar-surigao-2026';
        $this->get('/services/training-seminars')->assertOk()->assertSee('Upcoming seminar')->assertSee('Fully booked!')->assertSee('href="'.$url.'"', false);
        $this->get($url)->assertOk()->assertSee('Solar Photovoltaic Installation')->assertSee('November 28–29, 2026')
            ->assertSee('Engr. Joselito S. Baldapan, PEE')->assertSee('sem-full', false)->assertSee('Fully booked!')->assertDontSee('Reserve a Slot')->assertSee('seminar-surigao-2026-poster-v2-640.jpg', false)
            ->assertSee('aria-current="page"', false)->assertDontSee('This seminar has ended');
        $this->get('/contact?from=seminar&seminar=solar-pv-installation-seminar-surigao-2026')->assertOk()->assertSee('I would like to join the 2-Day Seminar', false);
        $this->get('/sitemap.xml')->assertSee($url);
        $this->get('/services/training-seminars/nope')->assertNotFound();

        $this->travelTo(\Carbon\CarbonImmutable::parse('2026-11-30 08:00', 'Asia/Manila'));
        $this->get('/services/training-seminars')->assertOk()->assertDontSee('Upcoming seminar');
        $this->get($url)->assertOk()->assertSee('This seminar has ended')->assertDontSee('Reserve a Slot')->assertDontSee('Fully booked!');
    }
}
