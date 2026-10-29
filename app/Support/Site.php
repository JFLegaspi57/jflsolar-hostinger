<?php

namespace App\Support;

/** Fixed brand facts and navigation. Editable contact details live in site_settings. */
final class Site
{
    public const NAME = 'JFL Solar';
    public const TAGLINE = 'PV System Solutions';
    public const AUDIENCES = 'For residential, industrial and commercial';
    public const DESCRIPTION = 'Solar PV installation, consultancy and equipment distribution for homes and businesses across the Philippines.';

    public const NAV = [
        ['href' => '/services', 'label' => 'Solar Services'],
        ['href' => '/pv-calculator', 'label' => 'PV Calculator'],
        ['href' => '/packages', 'label' => 'Packages'],
        ['href' => '/products', 'label' => 'Products'],
        ['href' => '/projects', 'label' => 'Projects'],
        ['href' => '/about', 'label' => 'About'],
        ['href' => '/contact', 'label' => 'Contact'],
    ];

    public const QUOTE_HREF = '/contact#quote';

    /** Search engines may index the public pages: live mode and indexing not switched off. */
    public static function allowsIndexing(): bool
    {
        return self::isProduction() && config('site.indexing');
    }

    public static function isProduction(): bool
    {
        return config('site.env') === 'production';
    }

    public static function url(string $path = ''): string
    {
        return config('site.url').$path;
    }

    public static function telHref(string $e164): string
    {
        return 'tel:'.$e164;
    }

    public static function whatsappHref(string $e164): string
    {
        return 'https://wa.me/'.preg_replace('/\D/', '', $e164);
    }

    /** Viber deep link; opens the Viber app if installed. */
    public static function viberHref(string $e164): string
    {
        return 'viber://chat?number='.rawurlencode($e164);
    }
}
