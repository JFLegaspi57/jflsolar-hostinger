<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HybridGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_hybrid_guide_is_published_and_linked(): void
    {
        $this->seed();
        $this->get('/hybrid-pv-system-guide')->assertOk()
            ->assertSee('How a hybrid solar PV system works')
            ->assertSee('Inside the hybrid inverter')->assertSee('What happens in a brownout')
            ->assertSee('<svg viewBox="0 0 760 430" role="img"', false);
        $this->get('/')->assertOk()->assertSee('href="/hybrid-pv-system-guide"', false);
        $this->get('/services')->assertOk()->assertSee('href="/hybrid-pv-system-guide"', false);
        $this->get('/sitemap.xml')->assertOk()->assertSee('/hybrid-pv-system-guide');
    }
}
