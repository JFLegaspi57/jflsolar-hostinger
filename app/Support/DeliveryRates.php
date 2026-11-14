<?php

namespace App\Support;

/**
 * Delivery rates page (/delivery-rates): estimated one-way delivery fees from the
 * Dasmariñas warehouse to towns and cities within about 100 km.
 *
 * Fees follow courier truck per-km rates (checked November 2026):
 * - Van (1,000 kg L300 / cargo van, cargo up to 2.1 m long): inverters, batteries and parts.
 * - Long van (2,000 kg FB-type long van, cargo up to 3 m long): any order with solar panels,
 *   because panels are longer than the small van's cargo space.
 * Distances are approximate road distances from the warehouse. To change a rate, edit the
 * constants below; every fee on the page is worked out from them.
 */
class DeliveryRates
{
    public const CHECKED = 'November 2026';

    public const MAX_KM = 100;

    /** Small van: base fare, then [up to km, pesos per km] tiers. */
    public const VAN_BASE = 280;

    public const VAN_TIERS = [[10, 24], [20, 22], [50, 20], [PHP_INT_MAX, 18]];

    /** Long van: base fare plus pesos per km. */
    public const LONG_VAN_BASE = 900;

    public const LONG_VAN_PER_KM = 26;

    /** Fees are rounded up to the next multiple of this. */
    public const ROUND_TO = 50;

    /** Province => [[town or city, approx. road km from the warehouse]], nearest first. */
    public const AREAS = [
        'Cavite' => [
            ['Dasmariñas', 5],
            ['General Mariano Alvarez', 10],
            ['Carmona', 15],
            ['Silang', 15],
            ['Trece Martires', 15],
            ['General Trias', 20],
            ['Imus', 20],
            ['Indang', 20],
            ['Tanza', 20],
            ['Amadeo', 25],
            ['Bacoor', 25],
            ['Kawit', 25],
            ['Noveleta', 25],
            ['Rosario', 25],
            ['Alfonso', 30],
            ['Cavite City', 30],
            ['Mendez', 30],
            ['Naic', 30],
            ['Tagaytay', 30],
            ['General Emilio Aguinaldo', 35],
            ['Magallanes', 35],
            ['Maragondon', 35],
            ['Ternate', 40],
        ],
        'Laguna' => [
            ['San Pedro', 15],
            ['Biñan', 20],
            ['Santa Rosa', 25],
            ['Cabuyao', 30],
            ['Calamba', 35],
            ['Los Baños', 50],
            ['Bay', 55],
            ['Alaminos', 65],
            ['Calauan', 65],
            ['Victoria', 65],
            ['Pila', 70],
            ['San Pablo City', 75],
            ['Magdalena', 80],
            ['Nagcarlan', 80],
            ['Rizal', 80],
            ['Santa Cruz', 80],
            ['Liliw', 85],
            ['Lumban', 85],
            ['Pagsanjan', 85],
            ['Majayjay', 90],
            ['Cavinti', 95],
            ['Kalayaan', 95],
            ['Paete', 95],
            ['Luisiana', 100],
            ['Pakil', 100],
            ['Pangil', 100],
        ],
        'Batangas' => [
            ['Talisay', 35],
            ['Laurel', 45],
            ['Santo Tomas', 46],
            ['Tanauan', 50],
            ['Balete', 55],
            ['Malvar', 55],
            ['Agoncillo', 60],
            ['Tuy', 60],
            ['Calaca', 65],
            ['Lian', 65],
            ['Mataasnakahoy', 65],
            ['Nasugbu', 65],
            ['San Nicolas', 65],
            ['Lipa City', 68],
            ['Balayan', 70],
            ['Cuenca', 70],
            ['Lemery', 70],
            ['Taal', 70],
            ['Alitagtag', 75],
            ['San Jose', 75],
            ['San Luis', 75],
            ['Santa Teresita', 75],
            ['Bauan', 85],
            ['Ibaan', 85],
            ['Padre Garcia', 85],
            ['Rosario', 85],
            ['San Pascual', 85],
            ['Batangas City', 89],
            ['Calatagan', 95],
            ['Mabini', 95],
            ['Taysan', 95],
        ],
        'Metro Manila' => [
            ['Las Piñas', 20],
            ['Muntinlupa', 20],
            ['Parañaque', 25],
            ['Pasay', 30],
            ['Taguig', 30],
            ['Makati', 35],
            ['Pateros', 35],
            ['Mandaluyong', 40],
            ['Manila', 40],
            ['Pasig', 40],
            ['San Juan', 40],
            ['Caloocan', 45],
            ['Malabon', 50],
            ['Marikina', 50],
            ['Navotas', 50],
            ['Quezon City', 50],
            ['Valenzuela', 55],
        ],
    ];

    public static function vanFee(int $km): int
    {
        $fee = self::VAN_BASE;
        $from = 0;
        foreach (self::VAN_TIERS as [$upTo, $rate]) {
            if ($km <= $from) {
                break;
            }
            $fee += (min($km, $upTo) - $from) * $rate;
            $from = $upTo;
        }

        return self::roundUp($fee);
    }

    public static function longVanFee(int $km): int
    {
        return self::roundUp(self::LONG_VAN_BASE + self::LONG_VAN_PER_KM * $km);
    }

    /** @return array<string, list<array{town: string, km: int, van: int, long_van: int, id: string}>> */
    public static function areas(): array
    {
        $out = [];
        foreach (self::AREAS as $province => $towns) {
            foreach ($towns as [$town, $km]) {
                if ($km > self::MAX_KM) {
                    continue;
                }
                $out[$province][] = ['town' => $town, 'km' => $km, 'van' => self::vanFee($km), 'long_van' => self::longVanFee($km),
                    'id' => \Illuminate\Support\Str::slug($province.' '.$town)];
            }
        }

        return $out;
    }

    /** Lowest and highest fee per vehicle, for the page intro. */
    public static function range(): array
    {
        $rows = collect(self::areas())->flatten(1);

        return ['van' => [$rows->min('van'), $rows->max('van')], 'long_van' => [$rows->min('long_van'), $rows->max('long_van')]];
    }

    public static function peso(int $amount): string
    {
        return '₱'.number_format($amount);
    }

    private static function roundUp(int $fee): int
    {
        return (int) (ceil($fee / self::ROUND_TO) * self::ROUND_TO);
    }
}
