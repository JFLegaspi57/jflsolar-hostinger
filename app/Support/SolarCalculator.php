<?php

namespace App\Support;

/**
 * Solar sizing calculator (public /solar-calculator page).
 *
 * Everything runs on the server from the GET query, so the page works without
 * JavaScript. Results are planning estimates only — no prices are shown, because
 * pricing comes from a JFL Solar quotation after a site assessment.
 */
final class SolarCalculator
{
    public const TYPES = [
        'grid' => ['label' => 'Grid-tied', 'short' => 'grid-tied', 'body' => 'Solar panels and an inverter connected to the utility. Cuts your daytime bill; no power during brownouts.'],
        'hybrid' => ['label' => 'Hybrid', 'short' => 'hybrid', 'body' => 'Solar with a battery, still connected to the utility. Stores daytime solar for the evening and keeps essentials on during brownouts.'],
        'offgrid' => ['label' => 'Off-grid', 'short' => 'off-grid', 'body' => 'No utility connection. Panels and batteries carry the whole load, so the system is sized with extra margin.'],
    ];

    /** Share of daily use that happens in daytime (6 AM – 6 PM), in percent. */
    public const PROFILES = [
        70 => ['label' => 'Mostly daytime', 'body' => 'Someone is home all day, a home office or a shop.'],
        50 => ['label' => 'Even through the day', 'body' => 'Use is spread across day and night.'],
        30 => ['label' => 'Mostly evening', 'body' => 'Everyone is out by day; aircon and appliances run at night.'],
    ];

    /** Common appliances: name, typical running watts, day hours, night hours. */
    public const APPLIANCES = [
        ['Aircon 1.0 HP (inverter)', 700, 0, 8],
        ['Aircon 1.5 HP (inverter)', 1000, 0, 0],
        ['Aircon 2.0 HP (non-inverter)', 1800, 0, 0],
        ['Refrigerator', 120, 12, 12],
        ['Chest freezer', 150, 0, 0],
        ['Electric fan', 60, 4, 6],
        ['LED lights (per bulb)', 10, 0, 6],
        ['Television', 100, 2, 4],
        ['Desktop computer', 200, 0, 0],
        ['Laptop', 60, 0, 0],
        ['Wi-Fi router', 15, 12, 12],
        ['Washing machine', 500, 1, 0],
        ['Rice cooker', 700, 0, 1],
        ['Microwave oven', 1000, 0, 0],
        ['Electric water heater', 3500, 0, 0],
        ['Water pump (1 HP)', 750, 1, 0],
    ];

    public const EXTRA_ROWS = 3;

    public const DEFAULTS = [
        'type' => 'hybrid', 'mode' => 'bill', 'bill' => 8000, 'rate' => 12.5, 'day' => 50,
        'sun' => 4.5, 'eff' => 80, 'panel' => 620, 'unit' => 16, 'export' => 6, 'days' => 1,
    ];

    private const GRID_INVERTERS = [3, 5, 6, 8, 10, 12, 15, 20];
    private const HYBRID_INVERTERS = [3, 5, 6, 8, 10, 12];
    private const BATTERY_USABLE = 0.9;   // depth of discharge
    private const ROUND_TRIP = 0.92;      // battery charge/discharge losses
    private const PANEL_AREA = 2.7;       // m² per ~620 W panel, with spacing

    /** Read and clamp the query string. Unknown or out-of-range values fall back to defaults. */
    public static function input(array $q): array
    {
        $d = self::DEFAULTS;
        $num = function (string $k, float $min, float $max) use ($q, $d): float {
            $v = $q[$k] ?? null;
            if (is_array($v) || $v === null || ! is_numeric(str_replace(',', '', (string) $v))) {
                return (float) $d[$k];
            }

            return max($min, min($max, (float) str_replace(',', '', (string) $v)));
        };

        $rows = [];
        $raw = is_array($q['a'] ?? null) ? $q['a'] : [];
        $count = count(self::APPLIANCES) + self::EXTRA_ROWS;
        for ($i = 0; $i < $count; $i++) {
            $r = is_array($raw[$i] ?? null) ? $raw[$i] : [];
            $preset = self::APPLIANCES[$i] ?? null;
            $n = fn (string $k, float $max, float $def) => isset($r[$k]) && ! is_array($r[$k]) && is_numeric($r[$k]) ? max(0, min($max, (float) $r[$k])) : $def;
            $name = $preset ? $preset[0] : mb_substr(trim(is_string($r['name'] ?? null) ? $r['name'] : ''), 0, 40);
            $rows[] = [
                'name' => $name,
                'custom' => $preset === null,
                'w' => $n('w', 10000, $preset[1] ?? 0),
                'q' => $n('q', 50, 0),
                'd' => $n('d', 12, $preset[2] ?? 0),
                'n' => $n('n', 12, $preset[3] ?? 0),
            ];
        }
        // Default list on first visit: a typical home (one aircon, fridge, fans, lights…).
        if (! $raw) {
            foreach ($rows as $i => &$row) {
                $row['q'] = match ($i) { 0, 3, 7, 10, 11, 12 => 1, 5 => 3, 6 => 8, 15 => 1, default => 0 };
            }
            unset($row);
        }

        return [
            'type' => array_key_exists($q['type'] ?? '', self::TYPES) ? $q['type'] : $d['type'],
            'mode' => in_array($q['mode'] ?? '', ['bill', 'appliances'], true) ? $q['mode'] : $d['mode'],
            'bill' => $num('bill', 0, 5000000),
            'rate' => $num('rate', 1, 50),
            'day' => (int) round($num('day', 0, 100)),
            'sun' => $num('sun', 2, 7),
            'eff' => $num('eff', 50, 95),
            'panel' => (int) $num('panel', 100, 800),
            'unit' => $num('unit', 1, 50),
            'export' => $num('export', 0, 50),
            'days' => $num('days', 0.5, 5),
            'rows' => $rows,
        ];
    }

    /** Size the system. Returns null when there is no usage to size for. */
    public static function calculate(array $in): ?array
    {
        if ($in['mode'] === 'appliances') {
            $day = $night = $connected = 0.0;
            foreach ($in['rows'] as $r) {
                $day += $r['w'] * $r['q'] * $r['d'] / 1000;
                $night += $r['w'] * $r['q'] * $r['n'] / 1000;
                if ($r['q'] > 0 && ($r['d'] + $r['n']) > 0) {
                    $connected += $r['w'] * $r['q'] / 1000;
                }
            }
        } else {
            $daily = $in['bill'] / $in['rate'] / 30;
            $day = $daily * $in['day'] / 100;
            $night = $daily - $day;
            $connected = null;
        }
        $daily = $day + $night;
        if ($daily < 0.3) {
            return null;
        }

        $type = $in['type'];
        $yield = $in['sun'] * $in['eff'] / 100;          // kWh per kWp per day

        // Battery: hybrid covers the evening; off-grid covers whole days of autonomy.
        $battery = 0.0;
        $units = 0;
        if ($type !== 'grid') {
            $need = $type === 'hybrid' ? $night : max($night, $daily * $in['days']);
            $units = max(1, (int) ceil($need / self::BATTERY_USABLE / $in['unit'] - 0.02));
            $battery = $units * $in['unit'];
        }

        // Array: grid-tied offsets the full day's use through net metering; battery systems
        // must also refill the battery; off-grid gets a 25% margin for cloudy days.
        $energy = match ($type) {
            'grid' => $daily,
            'hybrid' => $day + $night / self::ROUND_TRIP,
            'offgrid' => ($day + $night / self::ROUND_TRIP) * 1.25,
        };
        $panels = max(2, (int) ceil($energy / $yield * 1000 / $in['panel'] - 0.02));
        $kwp = $panels * $in['panel'] / 1000;

        // Inverter: matched to the array (up to ~130% DC/AC) and, if appliances were listed,
        // to 80% of everything that could run at once.
        $minKw = max($kwp / 1.3, $connected !== null ? $connected * 0.8 : 0, $type === 'grid' ? 0 : 3);
        $inverter = self::pickInverter($minKw, $type === 'grid' ? self::GRID_INVERTERS : self::HYBRID_INVERTERS);

        // Daily energy flows → savings.
        $gen = $kwp * $yield;
        $selfDay = min($day, $gen);
        $surplus = $gen - $selfDay;
        $fromBattery = $type === 'grid' ? 0 : min($night, $surplus * self::ROUND_TRIP, $battery * self::BATTERY_USABLE);
        $surplus -= $type === 'grid' ? 0 : $fromBattery / self::ROUND_TRIP;
        $exported = $type === 'offgrid' ? 0 : max(0, min($surplus, $night - $fromBattery));
        $monthlyUse = $daily * 30;
        $savings = $type === 'offgrid'
            ? $monthlyUse * $in['rate']
            : (($selfDay + $fromBattery) * $in['rate'] + $exported * $in['export']) * 30;
        $bill = $monthlyUse * $in['rate'];

        $nightHours = 12;
        $backup = $type !== 'grid' && $night > 0 ? $battery * self::BATTERY_USABLE / ($night / $nightHours) : null;

        return [
            'daily' => $daily, 'day' => $day, 'night' => $night, 'monthly' => $monthlyUse, 'connected' => $connected,
            'kwp' => $kwp, 'panels' => $panels, 'panel' => $in['panel'], 'area' => $panels * self::PANEL_AREA,
            'inverter' => $inverter, 'battery' => $battery, 'units' => $units, 'unit' => $in['unit'],
            'backup' => $backup, 'nightCover' => $type === 'grid' || $night <= 0 ? null : min(1, $battery * self::BATTERY_USABLE / $night),
            'output' => $gen * 30, 'savings' => min($savings, $bill > 0 ? $bill : $savings), 'bill' => $bill,
            'offset' => $bill > 0 ? min(1, $savings / $bill) : null,
        ];
    }

    /** Smallest standard size that fits; above the largest, several identical units. */
    private static function pickInverter(float $minKw, array $sizes): array
    {
        foreach ($sizes as $s) {
            if ($s >= $minKw - 0.01) {
                return ['count' => 1, 'kw' => $s, 'label' => $s.' kW'];
            }
        }
        $max = end($sizes);
        $count = (int) ceil($minKw / $max);
        foreach ($sizes as $s) {
            if ($s * $count >= $minKw - 0.01) {
                return ['count' => $count, 'kw' => $s, 'label' => $count.' × '.$s.' kW'];
            }
        }

        return ['count' => $count, 'kw' => $max, 'label' => $count.' × '.$max.' kW'];
    }

    /** One-paragraph summary for a quotation request. */
    public static function summary(array $in, array $r): string
    {
        $type = self::TYPES[$in['type']]['short'];
        $parts = [
            'From the solar calculator: about '.self::num($r['kwp'], 2).' kWp '.$type.' system',
            '('.$r['panels'].' × '.$in['panel'].' W panels, '.$r['inverter']['label'].' inverter'
                .($r['battery'] > 0 ? ', '.self::num($r['battery'], 1).' kWh battery' : '').').',
            'Estimated use: '.self::num($r['monthly'], 0).' kWh a month'
                .($in['mode'] === 'bill' ? ' (bill about ₱'.number_format($in['bill']).')' : ' (from my appliance list)').'.',
            'Please send me a quotation.',
        ];

        return implode(' ', $parts);
    }

    /** Matching BILL_RANGES key for a monthly bill in pesos. */
    public static function billRange(float $bill): string
    {
        return match (true) {
            $bill <= 0 => '',
            $bill < 3000 => 'under-3k',
            $bill < 5000 => '3k-5k',
            $bill < 10000 => '5k-10k',
            $bill < 20000 => '10k-20k',
            $bill <= 50000 => '20k-50k',
            default => 'over-50k',
        };
    }

    public static function num(float $v, int $dp = 1): string
    {
        $s = number_format($v, $dp);

        return $dp > 0 ? rtrim(rtrim($s, '0'), '.') : $s;
    }
}
