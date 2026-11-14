<?php

namespace App\Support;

/**
 * Delivery rates page (/delivery-rates): estimated one-way delivery fees from the
 * Dasmariñas warehouse to towns and cities within about 250 km.
 *
 * Fees follow courier truck per-km rates (checked November 2026):
 * - Van (1,000 kg L300 / cargo van, cargo up to 2.1 m long): inverters, batteries and parts.
 * - Long van (2,000 kg FB-type long van, cargo up to 3 m long): any order with solar panels,
 *   because panels are longer than the small van's cargo space.
 * Distances are approximate road distances from the warehouse (land routes only; islands left out). To change a rate, edit the
 * constants below; every fee on the page is worked out from them.
 */
class DeliveryRates
{
    public const CHECKED = 'November 2026';

    public const MAX_KM = 250;

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
            ['Calauan', 60],
            ['Victoria', 60],
            ['Alaminos', 65],
            ['Pila', 70],
            ['San Pablo City', 70],
            ['Nagcarlan', 75],
            ['Rizal', 75],
            ['Santa Cruz', 75],
            ['Pagsanjan', 77],
            ['Liliw', 80],
            ['Magdalena', 80],
            ['Lumban', 85],
            ['Majayjay', 85],
            ['Kalayaan', 90],
            ['Cavinti', 95],
            ['Paete', 95],
            ['Pakil', 95],
            ['Luisiana', 100],
            ['Mabitac', 100],
            ['Pangil', 100],
            ['Siniloan', 100],
            ['Famy', 105],
            ['Santa Maria', 110],
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
            ['San Juan', 105],
            ['Lobo', 115],
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
        'Rizal' => [
            ['Cainta', 50],
            ['Taytay', 50],
            ['Angono', 55],
            ['Antipolo', 55],
            ['Teresa', 60],
            ['Binangonan', 65],
            ['Morong', 65],
            ['San Mateo', 65],
            ['Rodriguez', 68],
            ['Baras', 70],
            ['Cardona', 70],
            ['Pililla', 75],
            ['Tanay', 75],
            ['Jalajala', 90],
        ],
        'Bulacan' => [
            ['Obando', 55],
            ['Meycauayan', 60],
            ['Marilao', 65],
            ['Balagtas', 70],
            ['Bocaue', 70],
            ['Bulakan', 70],
            ['San Jose del Monte', 70],
            ['Santa Maria', 70],
            ['Guiguinto', 75],
            ['Pandi', 75],
            ['Malolos', 80],
            ['Paombong', 80],
            ['Angat', 85],
            ['Hagonoy', 85],
            ['Norzagaray', 85],
            ['Plaridel', 85],
            ['Pulilan', 85],
            ['Baliuag', 90],
            ['Bustos', 90],
            ['Calumpit', 95],
            ['Doña Remedios Trinidad', 95],
            ['San Rafael', 95],
            ['San Ildefonso', 105],
            ['San Miguel', 115],
        ],
        'Pampanga' => [
            ['Apalit', 95],
            ['Macabebe', 95],
            ['Masantol', 95],
            ['San Fernando', 95],
            ['Bacolor', 100],
            ['Mexico', 100],
            ['San Simon', 100],
            ['Santo Tomas', 100],
            ['Guagua', 105],
            ['Lubao', 105],
            ['Minalin', 105],
            ['San Luis', 105],
            ['Santa Rita', 105],
            ['Sasmuan', 105],
            ['Candaba', 110],
            ['Santa Ana', 110],
            ['Angeles City', 115],
            ['Arayat', 115],
            ['Floridablanca', 115],
            ['Porac', 115],
            ['Mabalacat', 116],
            ['Magalang', 120],
        ],
        'Bataan' => [
            ['Hermosa', 130],
            ['Dinalupihan', 135],
            ['Orani', 135],
            ['Samal', 135],
            ['Abucay', 140],
            ['Balanga', 150],
            ['Pilar', 150],
            ['Orion', 155],
            ['Limay', 160],
            ['Bagac', 165],
            ['Morong', 170],
            ['Mariveles', 180],
        ],
        'Quezon' => [
            ['Tiaong', 80],
            ['Dolores', 85],
            ['San Antonio', 85],
            ['Candelaria', 95],
            ['Lucban', 105],
            ['Sariaya', 105],
            ['Lucena', 115],
            ['Sampaloc', 115],
            ['Tayabas', 115],
            ['Real', 120],
            ['Mauban', 125],
            ['General Nakar', 130],
            ['Infanta', 130],
            ['Pagbilao', 130],
            ['Padre Burgos', 150],
            ['Atimonan', 160],
            ['Agdangan', 165],
            ['Plaridel', 175],
            ['Unisan', 175],
            ['Gumaca', 190],
            ['Pitogo', 190],
            ['Macalelon', 200],
            ['General Luna', 210],
            ['Lopez', 210],
            ['Calauag', 215],
            ['Catanauan', 235],
            ['Guinayangan', 240],
            ['Buenavista', 245],
            ['Mulanay', 250],
            ['Tagkawayan', 250],
        ],
        'Tarlac' => [
            ['Bamban', 130],
            ['Capas', 135],
            ['Concepcion', 135],
            ['La Paz', 150],
            ['Tarlac City', 150],
            ['San Jose', 160],
            ['Gerona', 165],
            ['Victoria', 165],
            ['Pura', 170],
            ['Paniqui', 175],
            ['Ramos', 175],
            ['Santa Ignacia', 175],
            ['Anao', 180],
            ['Mayantoc', 180],
            ['Camiling', 185],
            ['Moncada', 185],
            ['San Clemente', 190],
            ['San Manuel', 190],
        ],
        'Nueva Ecija' => [
            ['Cabiao', 135],
            ['Gapan', 140],
            ['Cabanatuan', 145],
            ['Jaen', 145],
            ['San Antonio', 145],
            ['San Isidro', 145],
            ['General Tinio', 150],
            ['Peñaranda', 150],
            ['San Leonardo', 150],
            ['Santa Rosa', 155],
            ['Aliaga', 160],
            ['Talavera', 160],
            ['General Mamerto Natividad', 165],
            ['Palayan', 165],
            ['Santo Domingo', 165],
            ['Zaragoza', 165],
            ['Llanera', 170],
            ['Quezon', 170],
            ['Bongabon', 175],
            ['Gabaldon', 175],
            ['Licab', 175],
            ['Laur', 180],
            ['Science City of Muñoz', 180],
            ['Guimba', 185],
            ['Nampicuan', 185],
            ['Rizal', 185],
            ['San Jose City', 190],
            ['Talugtug', 190],
            ['Cuyapo', 195],
            ['Pantabangan', 200],
            ['Lupao', 205],
            ['Carranglan', 215],
        ],
        'Zambales' => [
            ['Olongapo', 150],
            ['Subic', 160],
            ['Castillejos', 165],
            ['San Marcelino', 170],
            ['San Antonio', 180],
            ['San Narciso', 180],
            ['Cabangan', 185],
            ['Botolan', 195],
            ['Iba', 205],
            ['Masinloc', 220],
            ['Palauig', 220],
            ['Candelaria', 230],
            ['Santa Cruz', 245],
        ],
        'Pangasinan' => [
            ['Bautista', 190],
            ['Bayambang', 195],
            ['Alcala', 200],
            ['Santo Tomas', 200],
            ['Balungao', 205],
            ['Basista', 205],
            ['Mangatarem', 205],
            ['Rosales', 205],
            ['Urbiztondo', 205],
            ['Villasis', 205],
            ['Malasiqui', 210],
            ['San Carlos City', 215],
            ['Santa Maria', 215],
            ['Umingan', 215],
            ['Urdaneta', 215],
            ['Aguilar', 220],
            ['Asingan', 220],
            ['San Quintin', 220],
            ['Santa Barbara', 220],
            ['Binalonan', 225],
            ['Calasiao', 225],
            ['Laoac', 225],
            ['Manaoag', 225],
            ['Mapandan', 225],
            ['San Manuel', 225],
            ['Tayug', 225],
            ['Bugallon', 230],
            ['Dagupan', 230],
            ['Mangaldan', 230],
            ['Natividad', 230],
            ['San Jacinto', 230],
            ['San Nicolas', 230],
            ['Binmaley', 235],
            ['Lingayen', 235],
            ['Pozorrubio', 235],
            ['San Fabian', 240],
            ['Sison', 240],
            ['Labrador', 245],
            ['Infanta', 250],
            ['Sual', 250],
        ],
        'Aurora' => [
            ['Dingalan', 215],
            ['Maria Aurora', 240],
            ['San Luis', 240],
            ['Baler', 250],
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
