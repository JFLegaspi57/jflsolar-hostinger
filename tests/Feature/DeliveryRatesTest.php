<?php

namespace Tests\Feature;

use App\Support\DeliveryRates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryRatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_fees_follow_the_rate_constants(): void
    {
        // Small van: 280 + 10×24 + 10×22 + 30×20 + 39×18 = 2,042 → rounded up to 2,050.
        $this->assertSame(2050, DeliveryRates::vanFee(89));
        $this->assertSame(400, DeliveryRates::vanFee(5)); // 280 + 120
        // Long van: 900 + 89×26 = 3,214 → 3,250.
        $this->assertSame(3250, DeliveryRates::longVanFee(89));
    }

    public function test_every_listed_town_is_within_the_radius(): void
    {
        foreach (DeliveryRates::areas() as $towns) {
            foreach ($towns as $t) {
                $this->assertLessThanOrEqual(DeliveryRates::MAX_KM, $t['km']);
            }
        }
        $this->assertSame(['Cavite', 'Laguna', 'Batangas', 'Metro Manila'], array_keys(DeliveryRates::areas()));
    }

    public function test_page_lists_towns_and_fees(): void
    {
        $this->get('/delivery-rates')->assertOk()
            ->assertSee('Delivery rates')
            ->assertSee('Batangas City')
            ->assertSee('Lipa City')
            ->assertSee('Calamba')
            ->assertSee('₱2,050')
            ->assertSee('₱3,250')
            ->assertSee('id="dr-town"', false);
    }

    public function test_page_is_linked_and_in_the_sitemap(): void
    {
        $this->get('/faq')->assertSee('href="/delivery-rates"', false);
        $this->get('/sitemap.xml')->assertSee('/delivery-rates');
    }
}
