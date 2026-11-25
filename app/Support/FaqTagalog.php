<?php

namespace App\Support;

use App\Models\SiteSetting;

/**
 * Tagalog version of the FAQ (/faq/tagalog). Same questions, numbering and links as App\Support\Faq;
 * technical terms stay in English, as customers use them. Unlike the English FAQ, this wording is
 * kept in code (not in Admin → Page text).
 */
final class FaqTagalog
{
    public const TITLE = 'Mga madalas itanong';

    public const INTRO = 'Mabilis na sagot tungkol sa solar PV system, quotation, pag-order, delivery, warranty, support at mga tool sa website namin. Wala rito ang tanong mo? Magtanong sa forum, tumawag o mag-message sa amin.';

    /** Question number => [question, answer]. {order} and {phone} are filled in as on the English page. */
    public const QA = [
        1 => ['Ano ang ginagawa ng JFL Solar?', 'Nag-i-install kami ng solar PV system, nagpapayo kung anong sistema ang bagay sa iyo, at nagsu-supply ng solar PV equipment: solar panel, inverter, battery at iba pang piyesa. Nagsisilbi kami sa mga may-ari ng bahay, commercial at industrial na kliyente, DIY buyers at professional installers sa buong Pilipinas. May mga libreng tool din ang website namin: ang PV Calculator, Make a Quotation, delivery rates, forum at mga step-by-step na gabay.'],
        2 => ['Anong sistema ang kailangan ko: on-grid, hybrid o off-grid?', 'Walang battery ang on-grid: binababa nito ang bill mo sa araw pero walang kuryente kapag brownout. May battery ang hybrid, kaya ang naipong kuryente ay puwedeng magpatakbo ng mahahalagang appliance sa gabi o kapag brownout. Walang koneksyon sa grid ang off-grid, kaya kailangan itong sukatin para sa lahat ng load at bawat panahon. Kinukumpirma namin kung alin ang bagay sa iyo sa assessment.'],
        3 => ['Gaano kalaking sistema ang kailangan ko?', 'Depende ito sa dami ng kuryenteng ginagamit mo. Ilagay ang buwanang bill mo o ang listahan ng appliances mo sa libreng PV Calculator para sa unang tantiya at mungkahing set ng piyesa. Puwede mong i-print ang resulta at dalhin kapag kinausap mo kami. Kinukumpirma namin ang final na laki pagkatapos tingnan ang bill mo at ang lugar mo.'],
        4 => ['Puwede ba akong bumili ng equipment lang, walang installation?', 'Oo. Pumili ng Materials Only bundle (inverter, battery at solar panel), ikumpara ang mga package, buuin ang sarili mong set gamit ang piyesang pipiliin mo, o bumili ng isa-isang produkto. Bagay ito sa DIY buyers at professional installers.'],
        5 => ['Ano ang Make a Quotation?', 'Libreng tool para sa mga rehistradong member. Pumili ng produkto mula sa catalogue namin na may presyo ngayon sa website, idagdag ang sarili mong items gaya ng labor o mounting, at i-save, baguhin at i-print ang listahan mo kahit ilang beses. Puwede kang magtabi ng hanggang 30 quotation para sa iba’t ibang proyekto. Kapag handa ka na, ipadala ito sa amin at sasagot ang team namin ng pormal na quotation.'],
        6 => ['Paano ako makakakuha ng quotation?', 'Tatlong paraan: buuin ang sarili mong listahan sa Make a Quotation, magpadala ng inquiry sa form sa Contact page namin, o tumawag sa {phone}. Para sa installation, isama ang buwanang bill mo sa kuryente at ang lokasyon mo para mas malapit ang tantiya namin.'],
        7 => ['Bakit “Request price” ang nakalagay sa ibang produkto?', 'Nagbabago ang presyo ng ilang item depende sa supply at stock. Idagdag ang item sa Make a Quotation, magpadala ng inquiry, o tumawag sa amin at ibibigay namin ang kasalukuyang presyo at availability.'],
        8 => ['Paano mag-order online at magbayad?', '{order}'],
        9 => ['Nagde-deliver ba kayo?', 'Oo. Nagde-deliver kami mula sa warehouse namin sa Dasmariñas, Cavite papunta sa mga bayan sa loob ng mga 250 km, kasama ang Metro Manila, Cavite, Laguna, Batangas, Rizal, Quezon, at malaking bahagi ng Central Luzon at Pangasinan. Sa Delivery Rates page namin, hanapin ang bayan mo para makita ang tantiyang bayad gamit ang van. Pang-pickup ang mga order sa online shop, kaya tumawag sa Sales team namin kung gusto mong ipa-deliver. Kinukumpirma namin ang final na bayad bago namin ipadala. Wala kaming cash on delivery (COD).'],
        10 => ['Nagseserbisyo ba kayo sa lugar ko?', 'Nagseserbisyo kami sa buong Pilipinas. Para sa mga bayang mas malayo kaysa sa listahan ng delivery rates, sabihin sa amin ang bayan mo at ang ino-order mo at kukuwentahin namin ang delivery. Kinukumpirma sa quotation ang schedule ng installation, site assessment at kung kaya ang proyekto.'],
        11 => ['Kailangan ko ba ng account? Ano ang makukuha ko kapag nag-sign up?', 'Puwede kang mag-browse, gumamit ng PV Calculator at mag-order kahit walang account. Sa libreng account, magagamit mo ang Make a Quotation, makakapagtanong at makakasagot ka sa forum, makikita mo ang mga order na ginawa mo habang naka-sign in, at puwede mong piliing tumanggap ng balita at promo namin sa email.'],
        12 => ['Saan ako puwedeng magtanong tungkol sa solar PV system ko?', 'Sa forum namin. Puwedeng basahin ito ng kahit sino, at puwedeng magtanong ang mga rehistradong member tungkol sa solar panel, inverter, battery, wiring at net metering. Sumasagot ang ibang may-ari at ang JFL Solar team. Sinusuri muna ng team namin ang unang post ng isang member bago ito lumabas.'],
        13 => ['Saan ako puwedeng matuto pa tungkol sa solar PV system?', 'Basahin ang mga libreng gabay namin kung paano gumagana ang hybrid at grid-tied PV system at tungkol sa net metering, sa English at Tagalog. Ipinapaliwanag ng mga brand page namin ang equipment na dala namin, at nagbabahagi ang blog ni JF Legaspi ng mga aral at kuwento mula sa field.'],
        14 => ['Puwede ko bang makita ang mga natapos ninyong proyekto?', 'Oo. Ipinapakita ng Projects gallery namin ang residential, commercial at industrial na solar PV work na nagawa na namin.'],
        15 => ['Anong warranty ang makukuha ko?', 'Ipinapakita ng bawat product page ang warranty ng manufacturer kung meron kami nito, at nakasulat sa quotation mo ang saklaw at proseso ng claim. Habang may warranty claim, nagbibigay ang suppliers namin ng kapalit o pansamantalang unit para sa inverter, battery at solar panel, at kami ang nag-aasikaso ng claim para tuloy-tuloy ang takbo ng sistema mo.'],
        16 => ['Anong support ang makukuha ko pagkatapos bumili?', 'Ang mga kliyenteng bumibili ng supplies sa amin ay may libreng technical support, mula installation at commissioning hanggang troubleshooting, kasama ang parameter settings ng inverter at battery. Sagot ka namin!'],
        17 => ['Puwede ko bang ipadala sa grid ang sobrang solar power ko (net metering)?', 'Oo, kung nakakonekta sa grid ang sistema mo at 100 kW o mas maliit, puwede kang mag-apply ng net metering sa electric utility mo, gaya ng Meralco o ng electric cooperative mo. Ang kuryenteng ipinapadala mo sa grid ay may katumbas na kredito sa piso na nagpapababa sa bill mo.'],
        18 => ['Paano ko makukuha ang balita at promo ninyo?', 'I-tick ang “Email me JFL Solar promos and news” kapag nag-sign up, o i-on ito mamaya sa account mo. May unsubscribe link ang bawat email, kaya puwede kang huminto anumang oras.'],
        19 => ['Ilang araw ang pag-install ng solar PV system?', 'Depende sa laki ng system. Ang 6, 8 o 10 kW na hybrid solar PV system ay 4 hanggang 5 araw. Ang 12, 16 o 18 kW na hybrid solar PV system ay karaniwang umaabot nang hanggang isang linggo, kung maganda ang panahon. Kapag umuulan, hindi kami nagtatrabaho sa bubong para sa kaligtasan ng aming technical team.'],
        20 => ['Gumagawa ba kayo ng one-day installation?', 'Hindi. Naniniwala kami na ang pagmamadali ay kadalasang nauuwi sa pagkakamali. Seryosong bagay ang solar PV system: pinaghirapang pera ng aming mga kliyente ang ipinupuhunan nila rito, kaya naglalaan kami ng sapat na oras para gawin nang tama ang bawat hakbang.'],
        21 => ['Puwede ko bang makita ang mga produkto bago ako bumili o bago ito ipadala?', 'Oo. Puwede mong makita at i-check ang mga item mo sa warehouse namin sa Dasmariñas, Cavite bago ka magbayad o bago namin ito ipadala. Pakitawagan muna ang Sales team namin sa {phone} para makapag-set kami ng oras at maihanda ang mga item mo. Nasa Contact page namin ang address, mapa at oras ng bukas.'],
        22 => ['Ano ang warranty ng mga inverter at battery?', 'Depende ito sa brand at model. May 5-year replacement warranty ang GoodWe hybrid inverters (GW6000 hanggang GW12K), at 10-year replacement warranty kapag ipinares mo ang GoodWe inverter sa GoodWe battery. May 5 taon na standard warranty sa Southeast Asia ang Solis hybrid inverters, at puwedeng i-extend. May 5 o 10 taon ang LuxPower hybrid inverters, depende sa model, at 2 taon ang LuxPower SNA 12K off-grid inverter. May 5-year warranty ang Dyness at Alltopelec (ATE) batteries. Nasa bawat product page ang eksaktong warranty, at nakasulat sa quotation mo ang terms at proseso ng claim. Para mag-claim, tumawag o mag-message sa amin kasama ang order o quotation number mo, maikling paliwanag ng problema at mga litrato kung mayroon.'],
    ];

    /** Link under an answer (same pages as the English FAQ; guides open in Tagalog). */
    public const LINKS = [
        2 => ['/services#system-types', 'Ikumpara ang mga uri ng sistema'],
        3 => ['/pv-calculator', 'Buksan ang PV Calculator'],
        4 => ['/packages?type=materials', 'Tingnan ang Materials Only bundles'],
        5 => ['/account/quotation', 'Buksan ang Make a Quotation'],
        6 => ['/contact#quote', 'Magpadala ng inquiry'],
        8 => ['/products', 'Tingnan ang mga produkto'],
        9 => ['/delivery-rates', 'Tingnan ang delivery rates'],
        11 => ['/signup', 'Mag-sign up nang libre'],
        12 => ['/forum', 'Pumunta sa forum'],
        13 => ['/hybrid-pv-system-guide/tagalog', 'Simulan sa gabay sa hybrid PV system'],
        14 => ['/projects', 'Tingnan ang mga proyekto'],
        16 => ['/services', 'Paano gumagana ang support namin'],
        17 => ['/net-metering/tagalog', 'Basahin ang gabay sa net metering'],
    ];

    /** @return list<array{q: string, a: string, link: ?array{0: string, 1: string}}> */
    public static function items(SiteSetting $settings): array
    {
        $tokens = [
            '{order}' => Shop::visible() ? self::howToBuy($settings) : 'Sarado muna ang online shop namin; magpadala ng inquiry o tumawag sa amin para mag-order.',
            '{phone}' => $settings->phone_display,
        ];
        $out = [];
        foreach (self::QA as $i => [$q, $a]) {
            $out[] = ['q' => $q, 'a' => trim(strtr($a, $tokens)), 'link' => self::LINKS[$i] ?? null];
        }

        return $out;
    }

    /** Tagalog version of Shop::howToBuy (same payment and pickup settings). */
    public static function howToBuy(SiteSetting $settings): string
    {
        $places = Shop::pickupLocations($settings);
        $where = count($places) > 1 ? 'aming office o warehouse' : 'aming '.strtolower(Shop::LOCATION_NAMES[array_key_first($places) ?? 'office']);
        $modes = Shop::paymentModes();
        $offline = array_map(fn ($c) => ['cash' => 'cash', 'gcash' => 'GCash', 'bank_transfer' => 'bank transfer', 'check' => 'tseke', 'card' => count($places) > 1 ? 'card (sa warehouse lang)' : 'card'][$c], Shop::inPersonChoices($settings));
        $offlineText = count($offline) > 1 ? implode(', ', array_slice($offline, 0, -1)).' o '.end($offline) : ($offline[0] ?? '');
        $online = str_replace(' or ', ' o ', Shop::methodsText());

        return match (true) {
            $modes === ['in_person'] => "Mag-order online, saka magbayad gamit ang {$offlineText} at i-pick up sa {$where}.",
            $modes === ['online'] => "Magbayad online gamit ang {$online}, saka i-pick up sa {$where}.",
            default => "Mag-order online at magbayad gamit ang {$offlineText}, o online gamit ang {$online}, saka i-pick up sa {$where}.",
        }.' Pakitawagan muna ang Sales team namin bago pumunta.';
    }
}
