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
        ['href' => '/services', 'label' => 'Services'],
        ['href' => '/pv-calculator', 'label' => 'PV Calculator'],
        ['href' => '/packages', 'label' => 'Packages'],
        ['href' => '/products', 'label' => 'Products'],
        ['href' => '/projects', 'label' => 'Projects'],
        ['href' => '/forum', 'label' => 'Forum'],
        ['href' => '/about', 'label' => 'About'],
        ['href' => '/contact', 'label' => 'Contact'],
    ];

    /** Help pages listed in the footer (not in the main menu). */
    public const HELP_LINKS = [
        ['href' => '/faq', 'label' => 'FAQ'],
        ['href' => '/net-metering', 'label' => 'Net metering guide'],
    ];

    public const QUOTE_HREF = '/contact#quote';

    /**
     * Search engines may index the public pages: live mode, and either indexing is on or the
     * visitor is on jflsolar.ph itself. SEARCH_INDEXING=false keeps temporary addresses
     * (e.g. Hostinger's *.hostingersite.com) out of search results; the real domain is indexed.
     */
    public static function allowsIndexing(): bool
    {
        return self::isProduction() && (config('site.indexing') || self::onOwnHost());
    }

    /** Google Analytics or the Meta Pixel is set up and this is the live site. */
    public static function tracking(): bool
    {
        return self::isProduction() && (config('site.ga4_id') !== '' || config('site.meta_pixel_id') !== '');
    }

    public static function isProduction(): bool
    {
        return config('site.env') === 'production';
    }

    /** The business's own web addresses, and the main one used in links, sitemaps and canonical tags. */
    public const OWN_HOSTS = ['jflsolar.ph', 'www.jflsolar.ph'];

    public const CANONICAL_URL = 'https://jflsolar.ph';

    /** The visitor is on jflsolar.ph (only these exact hosts count, so a forged Host header cannot). */
    public static function onOwnHost(): bool
    {
        return in_array(strtolower((string) request()->getHost()), self::OWN_HOSTS, true);
    }

    /**
     * Absolute site address: https://jflsolar.ph when the visitor is on jflsolar.ph (even if
     * APP_URL still holds a temporary address); otherwise APP_URL.
     */
    public static function url(string $path = ''): string
    {
        return (self::onOwnHost() ? self::CANONICAL_URL : config('site.url')).$path;
    }

    /** Same as url(); kept for links that leave the page (emails, printouts). */
    public static function publicUrl(string $path = ''): string
    {
        return self::url($path);
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
