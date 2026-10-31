<?php

namespace App\Support;

use App\Models\SiteSetting;

/** Frequently asked questions (wording editable in Admin → Page text → FAQ). */
final class Faq
{
    public const COUNT = 12;

    /** Question number => page linked under the answer. */
    public const LINKS = [
        2 => ['/services#system-types', 'Compare system types'],
        3 => ['/pv-calculator', 'Open the PV Calculator'],
        4 => ['/packages?type=materials', 'See Materials Only bundles'],
        9 => ['/services', 'How our support works'],
        10 => ['/net-metering', 'Read our net metering guide'],
        12 => ['/contact#quote', 'Request a Quote'],
    ];

    /** @return list<array{q: string, a: string, link: ?array{0: string, 1: string}}> */
    public static function items(SiteSetting $settings): array
    {
        $tokens = [
            '{order}' => Shop::visible() ? Shop::howToBuy($settings) : 'Our online shop is closed for now; send us a quote request or call us to order.',
            '{phone}' => $settings->phone_display,
        ];
        $out = [];
        for ($i = 1; $i <= self::COUNT; $i++) {
            $out[] = [
                'q' => PageText::get("faq.q{$i}.q"),
                'a' => trim(strtr(PageText::get("faq.q{$i}.a"), $tokens)),
                'link' => self::LINKS[$i] ?? null,
            ];
        }

        return $out;
    }

    /** FAQPage structured data for search engines. */
    public static function jsonLd(array $items): string
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn ($it) => [
                '@type' => 'Question',
                'name' => $it['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $it['a']],
            ], $items),
        ];

        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_THROW_ON_ERROR);
    }
}
