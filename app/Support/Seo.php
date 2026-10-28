<?php

namespace App\Support;

use App\Models\SiteSetting;

final class Seo
{
    /**
     * Organization structured data limited to verified facts: name, URL, logo,
     * phone, office address, service area. No ratings, reviews or sample
     * projects. The public email is omitted while it is an unconfirmed placeholder.
     */
    public static function organizationJsonLd(SiteSetting $s): string
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => Site::NAME,
            'url' => Site::url(),
            'logo' => Site::url('/brand/jfl-solar-logo.png'),
            'telephone' => $s->phone_e164,
            'address' => ['@type' => 'PostalAddress', 'streetAddress' => $s->office_address, 'addressCountry' => 'PH'],
            'areaServed' => ['@type' => 'Country', 'name' => 'Philippines'],
        ];
        if ($s->facebook_enabled && $s->facebook_url) {
            $data['sameAs'] = [$s->facebook_url];
        }

        // JSON_HEX_TAG keeps "</script>" from ending the tag.
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_THROW_ON_ERROR);
    }
}
