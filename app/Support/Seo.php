<?php

namespace App\Support;

use App\Models\SiteSetting;

final class Seo
{
    /**
     * Local business structured data (a LocalBusiness is also an Organization) limited to
     * verified facts: name, URL, logo, phone, email, office address, opening hours when they
     * read like "8AM to 5PM Monday to Saturday", map link and service area. No ratings,
     * reviews or sample projects.
     */
    public static function organizationJsonLd(SiteSetting $s): string
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => Site::NAME,
            'alternateName' => \App\Support\Shop::MAPS_NAME,
            'description' => Site::DESCRIPTION,
            'url' => Site::url(),
            'logo' => Site::url('/brand/jfl-solar-logo.png'),
            'image' => Site::url('/og-image.png'),
            'telephone' => $s->phone_e164,
            'address' => ['@type' => 'PostalAddress', 'streetAddress' => $s->office_address, 'addressCountry' => 'PH'],
            'hasMap' => \App\Support\Shop::directionLinks()['google'],
            'areaServed' => ['@type' => 'Country', 'name' => 'Philippines'],
        ];
        if (filled($s->public_email)) {
            $data['email'] = $s->public_email;
        }
        if ($hours = self::openingHours((string) $s->business_hours)) {
            $data['openingHoursSpecification'] = $hours;
        }
        if ($s->facebook_enabled && $s->facebook_url) {
            $data['sameAs'] = [$s->facebook_url];
        }

        // JSON_HEX_TAG keeps "</script>" from ending the tag.
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_THROW_ON_ERROR);
    }

    private const DAYS = ['monday' => 'Monday', 'mon' => 'Monday', 'tuesday' => 'Tuesday', 'tue' => 'Tuesday', 'tues' => 'Tuesday', 'wednesday' => 'Wednesday', 'wed' => 'Wednesday', 'thursday' => 'Thursday', 'thu' => 'Thursday', 'thurs' => 'Thursday', 'friday' => 'Friday', 'fri' => 'Friday', 'saturday' => 'Saturday', 'sat' => 'Saturday', 'sunday' => 'Sunday', 'sun' => 'Sunday'];

    /**
     * "8AM to 5PM Monday to Saturday" (also "8:00 AM - 5:00 PM, Mon-Sat") → schema.org hours.
     * Anything else returns null, so unusual wording is simply left out.
     */
    public static function openingHours(string $text): ?array
    {
        $t = '(\d{1,2})(?::(\d{2}))?\s*([ap])\.?\s*m\.?';
        $d = '([a-z]+)\.?';
        if (! preg_match("/^\s*{$t}\s*(?:to|-|–)\s*{$t}\s*,?\s*{$d}\s*(?:to|-|–)\s*{$d}\s*$/i", $text, $m)) {
            return null;
        }
        $from = self::DAYS[strtolower($m[7])] ?? null;
        $to = self::DAYS[strtolower($m[8])] ?? null;
        if (! $from || ! $to) {
            return null;
        }
        $clock = function (string $h, string $min, string $ap): ?string {
            $h = (int) $h;
            if ($h < 1 || $h > 12) {
                return null;
            }
            $h = $h % 12 + (strtolower($ap) === 'p' ? 12 : 0);

            return sprintf('%02d:%02d', $h, $min === '' ? 0 : (int) $min);
        };
        $opens = $clock($m[1], $m[2], $m[3]);
        $closes = $clock($m[4], $m[5], $m[6]);
        if (! $opens || ! $closes) {
            return null;
        }
        $week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $a = array_search($from, $week, true);
        $b = array_search($to, $week, true);
        $days = $a <= $b ? array_slice($week, $a, $b - $a + 1) : [...array_slice($week, $a), ...array_slice($week, 0, $b + 1)];

        return [['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => $days, 'opens' => $opens, 'closes' => $closes]];
    }
}
