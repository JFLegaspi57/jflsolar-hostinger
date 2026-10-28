<?php

namespace App\Support;

use App\Models\Package;
use Illuminate\Support\HtmlString;

/** Rows of the package comparison (shared by the desktop table and the mobile groups). */
final class Compare
{
    public const MAX = 3;

    /** @return list<array{label: string, render: \Closure(Package): HtmlString}> */
    public static function rows(): array
    {
        $text = fn (?string $s) => new HtmlString(e($s));
        $list = function (?array $items, string $empty) {
            $items = array_values(array_filter($items ?? []));
            if (! $items) {
                return new HtmlString('<span class="text-steel-600">'.e($empty).'</span>');
            }

            return new HtmlString('<ul class="list-disc space-y-0.5 pl-4">'.implode('', array_map(fn ($i) => '<li>'.e($i).'</li>', $items)).'</ul>');
        };

        return [
            ['label' => 'Offer type', 'render' => fn (Package $p) => new HtmlString('<strong>'.e(Labels::PACKAGE_TYPE[$p->package_type]).'</strong>')],
            ['label' => 'Installation', 'render' => fn (Package $p) => $text($p->installation_terms ?: ($p->package_type === 'installed_system' ? 'Included' : 'Not included'))],
            ['label' => 'System type', 'render' => fn (Package $p) => $text($p->system_type ? Labels::SYSTEM_TYPE[$p->system_type] : '—')],
            ['label' => 'Inverter (kW)', 'render' => fn (Package $p) => $text(self::capacity($p, 'inverter'))],
            ['label' => 'Solar array (kWp)', 'render' => fn (Package $p) => $text(self::capacity($p, 'array'))],
            ['label' => 'Battery (kWh)', 'render' => fn (Package $p) => $text(self::capacity($p, 'battery'))],
            ['label' => 'Price', 'render' => function (Package $p) {
                $d = $p->price($p->promo_state);

                return new HtmlString('<span><strong class="tabular">'.e($d['headline']).'</strong>'
                    .($d['isSample'] ? '<span class="block text-xs text-steel-600">Sample price — not a confirmed offer</span>' : '').'</span>');
            }],
            ['label' => 'Pricing mode and basis', 'render' => fn (Package $p) => $text(implode(' · ', array_filter([Labels::PRICING_MODE[$p->pricing_mode], $p->price_basis])))],
            ['label' => 'Promotion', 'render' => fn (Package $p) => $text($p->promo_state === 'none' ? '—'
                : ($p->promo_label ?? 'Promotion').' ('.($p->promo_state === 'ended' ? 'ended' : $p->promo_state).') '.(Pricing::promoWindow($p->promoStart(), $p->promoEnd()) ?? ''))],
            ['label' => 'Pickup and delivery', 'render' => fn (Package $p) => $text(implode('. ', array_filter([Labels::FULFILLMENT[$p->fulfillment], $p->fulfillment_notes])))],
            ['label' => 'Included', 'render' => fn (Package $p) => $list($p->inclusions, 'Confirmed in quotation')],
            ['label' => 'Not included', 'render' => fn (Package $p) => $list($p->exclusions, 'Confirmed in quotation')],
            ['label' => 'Warranty', 'render' => fn (Package $p) => $text($p->warranty_terms ?: 'Warranty terms to be confirmed')],
        ];
    }

    /** Mobile: one combined, labelled capacity row instead of three. */
    public static function mobileRows(): array
    {
        $capacity = ['Inverter (kW)', 'Solar array (kWp)', 'Battery (kWh)'];
        $out = array_values(array_filter(self::rows(), fn ($r) => ! in_array($r['label'], $capacity, true)));
        $at = array_search('System type', array_column($out, 'label'), true) + 1;
        array_splice($out, $at, 0, [[
            'label' => 'Capacity',
            'render' => fn (Package $p) => new HtmlString(e('Inverter '.self::capacity($p, 'inverter').' · Solar array '.self::capacity($p, 'array').' · Battery '.self::capacity($p, 'battery'))),
        ]]);

        return $out;
    }

    /**
     * Same wording as the package cards: all three empty = sized after a site
     * assessment; otherwise an empty value means "not included".
     */
    public static function capacity(Package $p, string $which): string
    {
        $values = [
            'inverter' => Pricing::formatCapacity($p->inverter_kw, 'kW'),
            'array' => Pricing::formatCapacity($p->array_kwp, 'kWp'),
            'battery' => Pricing::formatCapacity($p->battery_kwh, 'kWh'),
        ];
        $allEmpty = ! array_filter($values);

        return $values[$which] ?? ($allEmpty ? 'after assessment' : 'not included');
    }

    /** Short label for repeated use: text before an em dash, if any. */
    public static function shortName(string $name): string
    {
        return explode(' — ', $name)[0];
    }
}
