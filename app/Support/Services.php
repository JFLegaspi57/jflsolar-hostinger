<?php

namespace App\Support;

/**
 * Service structure (icons, links, order). The wording is editable in
 * Admin → Page text (PageTextData). No savings, payback or performance guarantees.
 */
final class Services
{
    private const SERVICE_LINKS = [
        ['icon' => 'tools', 'href' => '/services#installation'],
        ['icon' => 'bolt', 'href' => '/services#consultancy'],
        ['icon' => 'battery', 'href' => '/products'],
    ];

    /** @return list<array{key: string, icon: string, href: string, title: string, body: string, cta: string}> */
    public static function services(): array
    {
        $out = [];
        foreach (self::SERVICE_LINKS as $i => $s) {
            $n = $i + 1;
            $out[] = $s + [
                'key' => "service{$n}",
                'title' => PageText::get("home.service{$n}.title"),
                'body' => PageText::get("home.service{$n}.body"),
                'cta' => PageText::get("home.service{$n}.link"),
            ];
        }

        return $out;
    }

    /** Shared by the home and Solar Services pages. @return list<array{title: string, body: string}> */
    public static function processSteps(): array
    {
        return array_map(fn ($n) => [
            'title' => PageText::get("services.step{$n}.title"),
            'body' => PageText::get("services.step{$n}.body"),
        ], range(1, 6));
    }

    /** @return list<array{id: string, title: string, body: string, points: list<string>}> */
    public static function audiences(): array
    {
        $out = [];
        foreach (['residential', 'commercial', 'industrial'] as $i => $id) {
            $n = $i + 1;
            $out[] = [
                'id' => $id,
                'title' => PageText::get("services.audience{$n}.title"),
                'body' => PageText::get("services.audience{$n}.body"),
                'points' => PageText::lines(PageText::get("services.audience{$n}.points")),
            ];
        }

        return $out;
    }

    /** @return list<array{key: string, lead: string, title: string, body: string, suits: string}> */
    public static function systemTypes(): array
    {
        $out = [];
        foreach (['on_grid', 'hybrid', 'off_grid'] as $i => $key) {
            $n = $i + 1;
            $out[] = [
                'key' => $key,
                'lead' => PageText::get("services.type{$n}.lead"),
                'title' => PageText::get("services.type{$n}.title"),
                'body' => PageText::get("services.type{$n}.body"),
                'suits' => PageText::get("services.type{$n}.suits"),
            ];
        }

        return $out;
    }
}
