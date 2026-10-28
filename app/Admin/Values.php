<?php

namespace App\Admin;

use App\Models\ContentBlock;
use App\Models\Package;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Project;
use App\Models\SiteSetting;

/** Form values (all strings) for each editable record type. */
final class Values
{
    private static function s(mixed $v): string
    {
        if ($v === null) {
            return '';
        }
        if (is_float($v)) {
            return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
        }

        return (string) $v;
    }

    private static function b(?bool $v): string
    {
        return $v ? 'on' : '';
    }

    public static function package(?Package $p): array
    {
        if (! $p) {
            return ['package_type' => 'installed_system', 'pricing_mode' => 'quote', 'fulfillment' => 'to_be_confirmed', 'system_type' => ''];
        }

        return [
            'name' => $p->name, 'slug' => $p->slug, 'sku' => self::s($p->sku), 'summary' => $p->summary, 'description' => $p->description,
            'package_type' => $p->package_type, 'system_type' => self::s($p->system_type),
            'inverter_kw' => self::s($p->inverter_kw), 'array_kwp' => self::s($p->array_kwp), 'battery_kwh' => self::s($p->battery_kwh),
            'components' => collect($p->components ?? [])->map(fn ($c) => implode(' | ', array_filter([$c['label'] ?? '', $c['detail'] ?? '', $c['quantity'] ?? null])))->implode("\n"),
            'inclusions' => implode("\n", $p->inclusions ?? []), 'exclusions' => implode("\n", $p->exclusions ?? []),
            'pricing_mode' => $p->pricing_mode, 'price_php' => self::s($p->price_php), 'price_basis' => self::s($p->price_basis), 'price_is_sample' => self::b($p->price_is_sample),
            'installation_terms' => self::s($p->installation_terms), 'fulfillment' => $p->fulfillment, 'fulfillment_notes' => self::s($p->fulfillment_notes),
            'promo_label' => self::s($p->promo_label), 'promo_start' => self::s($p->promoStart()), 'promo_end' => self::s($p->promoEnd()), 'hide_when_expired' => self::b($p->hide_when_expired),
            'warranty_terms' => self::s($p->warranty_terms), 'admin_notes' => self::s($p->admin_notes), 'featured' => self::b($p->featured), 'is_sample' => self::b($p->is_sample),
        ];
    }

    public static function product(?Product $p): array
    {
        if (! $p) {
            return ['pricing_mode' => 'quote', 'availability' => 'on_request'];
        }

        return [
            'name' => $p->name, 'slug' => $p->slug, 'sku' => self::s($p->sku), 'brand' => self::s($p->brand), 'model' => self::s($p->model), 'category_id' => $p->category_id,
            'summary' => $p->summary, 'description' => $p->description,
            'specs' => collect($p->specs ?? [])->map(fn ($x) => ($x['label'] ?? '').': '.($x['value'] ?? ''))->implode("\n"),
            'pricing_mode' => $p->pricing_mode, 'price_php' => self::s($p->price_php), 'price_basis' => self::s($p->price_basis), 'price_is_sample' => self::b($p->price_is_sample),
            'availability' => $p->availability, 'warranty_terms' => self::s($p->warranty_terms), 'admin_notes' => self::s($p->admin_notes),
            'featured' => self::b($p->featured), 'is_sample' => self::b($p->is_sample),
        ];
    }

    public static function project(?Project $p): array
    {
        if (! $p) {
            return ['project_type' => 'residential'];
        }

        return [
            'title' => $p->title, 'slug' => $p->slug, 'location' => self::s($p->location), 'project_type' => $p->project_type,
            'system_size_kwp' => self::s($p->system_size_kwp), 'system_size_confirmed' => self::b($p->system_size_confirmed), 'scope' => self::s($p->scope),
            'summary' => $p->summary, 'description' => $p->description, 'admin_notes' => self::s($p->admin_notes),
            'featured' => self::b($p->featured), 'is_sample' => self::b($p->is_sample),
        ];
    }

    public static function category(?ProductCategory $c): array
    {
        return $c ? ['name' => $c->name, 'slug' => $c->slug, 'description' => self::s($c->description)] : [];
    }

    public static function block(?ContentBlock $x): array
    {
        if (! $x) {
            return ['section' => 'about', 'published' => 'on', 'sort_order' => '100'];
        }

        return [
            'key' => $x->key, 'section' => $x->section, 'title' => $x->title, 'subtitle' => self::s($x->subtitle), 'body' => $x->body,
            'confirmed' => self::b($x->claim_status === 'confirmed'), 'review_note' => self::s($x->review_note),
            'published' => self::b($x->published), 'sort_order' => self::s($x->sort_order),
        ];
    }

    public static function settings(SiteSetting $x): array
    {
        $out = [];
        foreach ($x->getAttributes() as $k => $v) {
            $out[$k] = in_array($k, ['whatsapp_enabled', 'viber_enabled', 'facebook_enabled', 'messenger_enabled'], true) ? self::b((bool) $v) : self::s($v);
        }

        return $out;
    }
}
