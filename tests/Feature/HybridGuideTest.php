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

    public function test_grid_tied_guide_is_published_and_linked(): void
    {
        $this->seed();
        $this->get('/grid-tied-pv-system-guide')->assertOk()
            ->assertSee('How a grid-tied solar PV system works')->assertSee('Why it switches off in a brownout')
            ->assertSee('<svg viewBox="0 0 760 380" role="img"', false);
        $this->get('/')->assertOk()->assertSee('href="/grid-tied-pv-system-guide"', false);
        $this->get('/hybrid-pv-system-guide')->assertOk()->assertSee('href="/grid-tied-pv-system-guide"', false);
        $this->get('/sitemap.xml')->assertOk()->assertSee('/grid-tied-pv-system-guide');
    }

    public function test_tagalog_hybrid_guide_is_published_and_linked_both_ways(): void
    {
        $this->seed();
        $this->get('/hybrid-pv-system-guide/tagalog')->assertOk()
            ->assertSee('<html lang="fil">', false)
            ->assertSee('Paano gumagana ang hybrid solar PV system')
            ->assertSee('Sa loob ng hybrid inverter')->assertSee('Ano ang nangyayari kapag brownout')
            ->assertSee('<svg viewBox="0 0 760 430" role="img"', false)
            ->assertSee('href="/hybrid-pv-system-guide" hreflang="en"', false)
            ->assertSee('hreflang="fil" href="', false)
            ->assertDontSee('solar system');
        $this->get('/hybrid-pv-system-guide')->assertOk()
            ->assertSee('<html lang="en-PH">', false)
            ->assertSee('href="/hybrid-pv-system-guide/tagalog" hreflang="fil"', false);
        $this->get('/sitemap.xml')->assertOk()->assertSee('/hybrid-pv-system-guide/tagalog');
    }

    public function test_tagalog_grid_tied_and_net_metering_guides_are_published_and_linked_both_ways(): void
    {
        $this->seed();
        $this->get('/grid-tied-pv-system-guide/tagalog')->assertOk()
            ->assertSee('<html lang="fil">', false)
            ->assertSee('Paano gumagana ang grid-tied solar PV system')->assertSee('Bakit ito namamatay kapag brownout')
            ->assertSee('<svg viewBox="0 0 760 380" role="img"', false)
            ->assertSee('href="/grid-tied-pv-system-guide" hreflang="en"', false)
            ->assertSee('href="/hybrid-pv-system-guide/tagalog"', false)->assertSee('href="/net-metering/tagalog"', false)
            ->assertDontSee('solar system');
        $this->get('/grid-tied-pv-system-guide')->assertOk()
            ->assertSee('href="/grid-tied-pv-system-guide/tagalog" hreflang="fil"', false);
        $this->get('/hybrid-pv-system-guide/tagalog')->assertOk()
            ->assertSee('href="/grid-tied-pv-system-guide/tagalog"', false)->assertDontSee('(sa Ingles)');

        $this->get('/net-metering/tagalog')->assertOk()
            ->assertSee('<html lang="fil">', false)
            ->assertSee('Net metering sa Pilipinas')->assertSee('Sino ang puwedeng mag-apply?')
            ->assertSee('href="/net-metering" hreflang="en"', false)
            ->assertDontSee('solar system');
        $this->get('/net-metering')->assertOk()
            ->assertSee('href="/net-metering/tagalog" hreflang="fil"', false)
            ->assertSee('<link rel="alternate" hreflang="fil"', false);

        $this->get('/sitemap.xml')->assertOk()
            ->assertSee('/grid-tied-pv-system-guide/tagalog')->assertSee('/net-metering/tagalog');
    }
}
