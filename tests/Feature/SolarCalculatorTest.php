<?php

namespace Tests\Feature;

use App\Support\SolarCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SolarCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_bill_mode_sizes_a_hybrid_system(): void
    {
        $in = SolarCalculator::input(['type' => 'hybrid', 'mode' => 'bill', 'bill' => '7500', 'rate' => '12.5', 'day' => '50']);
        $r = SolarCalculator::calculate($in);

        $this->assertEqualsWithDelta(20, $r['daily'], 0.01);           // 7500 / 12.5 / 30
        $this->assertSame(16.0, $r['battery']);                         // 10 kWh night ÷ 0.9 → one 16 kWh module
        $this->assertGreaterThanOrEqual(20.85 / 3.6, $r['kwp']);       // day + night/0.92 over 3.6 kWh/kWp
        $this->assertSame($r['panels'] * 620 / 1000, $r['kwp']);
        $this->assertGreaterThanOrEqual($r['kwp'] / 1.3, $r['inverter']['kw'] * $r['inverter']['count']);
        $this->assertLessThanOrEqual($r['bill'], $r['savings']);
    }

    public function test_grid_tied_has_no_battery_and_off_grid_has_margin(): void
    {
        $base = ['mode' => 'bill', 'bill' => '5000', 'rate' => '12.5', 'day' => '50'];
        $grid = SolarCalculator::calculate(SolarCalculator::input(['type' => 'grid'] + $base));
        $off = SolarCalculator::calculate(SolarCalculator::input(['type' => 'offgrid'] + $base));

        $this->assertSame(0.0, $grid['battery']);
        $this->assertNull($grid['backup']);
        $this->assertGreaterThan($grid['kwp'], $off['kwp']);
        $this->assertEqualsWithDelta(1, $off['offset'], 0.001);
    }

    public function test_appliance_mode_and_bad_input(): void
    {
        $in = SolarCalculator::input(['type' => 'grid', 'mode' => 'appliances', 'a' => [0 => ['w' => '1000', 'q' => '2', 'd' => '5', 'n' => '0']]]);
        $r = SolarCalculator::calculate($in);
        $this->assertEqualsWithDelta(10, $r['daily'], 0.001);
        $this->assertGreaterThanOrEqual(1.6, $r['inverter']['kw']);   // 80% of 2 kW connected

        $junk = SolarCalculator::input(['type' => 'x', 'bill' => ['a'], 'rate' => '-4', 'a' => 'nope']);
        $this->assertSame('hybrid', $junk['type']);
        $this->assertSame(1.0, $junk['rate']);
        $this->assertNull(SolarCalculator::calculate(SolarCalculator::input(['type' => 'grid', 'bill' => '0'])));
    }

    public function test_price_range_scales_from_real_installed_packages(): void
    {
        $pkg = fn (string $slug, float $kwp, ?float $price, array $extra = []) => (new \App\Models\Package)->forceFill($extra + [
            'slug' => $slug, 'name' => $slug, 'package_type' => 'installed_system', 'system_type' => 'hybrid', 'array_kwp' => $kwp,
            'battery_kwh' => 16, 'pricing_mode' => 'fixed', 'price_php' => $price, 'price_is_sample' => false, 'is_sample' => false,
        ]);
        $packages = [
            $pkg('a', 8.68, 460000), $pkg('b', 9.92, 550000),
            $pkg('sample', 9, 100000, ['is_sample' => true]),
            $pkg('equipment', 9, 100000, ['package_type' => 'equipment_bundle']),
            $pkg('quote', 9, null, ['pricing_mode' => 'quote']),
        ];
        $in = SolarCalculator::input(['type' => 'hybrid', 'mode' => 'bill', 'bill' => '8000', 'rate' => '12.5', 'day' => '50']);
        $r = SolarCalculator::calculate($in);
        $p = SolarCalculator::priceRange($in, $r, $packages);

        $this->assertSame(2, $p['count']);                                   // sample, equipment-only and quote-only ignored
        $this->assertLessThanOrEqual(460000 / 8.68 * $r['kwp'], $p['low']);
        $this->assertGreaterThanOrEqual(550000 / 9.92 * $r['kwp'], $p['high']);
        $this->assertSame('a', $p['closest']->slug);

        $grid = SolarCalculator::input(['type' => 'grid'] + $in);
        $this->assertNull(SolarCalculator::priceRange($grid, SolarCalculator::calculate($grid), $packages));
        $huge = SolarCalculator::input(['bill' => '90000'] + $in);
        $this->assertNull(SolarCalculator::priceRange($huge, SolarCalculator::calculate($huge), $packages));
    }

    public function test_page_renders_and_prefills_quote(): void
    {
        $this->get('/solar-calculator')->assertOk()->assertSee('Your results appear here');
        $this->get('/solar-calculator?type=hybrid&mode=bill&bill=8000&rate=12.5&day=30')
            ->assertOk()->assertSee('kWp')->assertSee('Request a quotation')->assertSee('from=calculator', false);
        $this->get('/solar-calculator?type=grid&mode=appliances')->assertOk();

        $this->get('/contact?from=calculator&type=hybrid&mode=bill&bill=8000&rate=12.5&day=30')
            ->assertOk()->assertSee('From the solar calculator: about');
    }
}
