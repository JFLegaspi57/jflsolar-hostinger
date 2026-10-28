<?php

namespace App\Support;

use App\Models\PageText as PageTextRow;
use Illuminate\Database\QueryException;

/**
 * Editable page wording (Admin → Page text). Defaults and the admin layout live
 * in PageTextData; staff edits are rows in page_texts. An empty field, or the
 * original wording, removes the row so the default shows again.
 */
final class PageText
{
    /** Wording for a key: the staff edit if any, otherwise the original. */
    public static function get(string $key): string
    {
        if (! array_key_exists($key, PageTextData::DEFAULTS)) {
            throw new \InvalidArgumentException("Unknown page text key: {$key}");
        }
        $override = PublicContent::snapshot()['texts'][$key] ?? '';

        return $override !== '' ? $override : PageTextData::DEFAULTS[$key];
    }

    /** "One item per line" fields → list items (leading bullets removed). */
    public static function lines(string $value): array
    {
        $out = [];
        foreach (explode("\n", $value) as $line) {
            $line = trim(preg_replace('/^[-•*]\s*/u', '', trim($line)));
            if ($line !== '') {
                $out[] = $line;
            }
        }

        return $out;
    }

    public static function page(string $slug): ?array
    {
        $page = PageTextData::PAGES[$slug] ?? null;

        return $page ? ['slug' => $slug] + $page : null;
    }

    /** @return list<array{key: string, label: string, kind: string}> */
    public static function fields(array $page): array
    {
        return array_merge(...array_map(fn ($g) => $g['fields'], $page['groups']));
    }

    /** @return list<string> */
    public static function keys(array $page): array
    {
        return array_column(self::fields($page), 'key');
    }

    /**
     * Validate and normalise one submitted value. Headings and paragraphs are shown
     * as one line/paragraph, so line breaks become spaces.
     *
     * @return array{value: string}|array{error: string}
     */
    public static function clean(array $field, string $raw): array
    {
        $v = str_replace(["\r\n", "\r"], "\n", $raw);
        $v = $field['kind'] === 'list'
            ? implode("\n", self::lines($v))
            : preg_replace('/\s*\n\s*/u', ' ', $v);
        $v = trim($v);
        $max = PageTextData::FIELD_MAX[$field['kind']];
        if (mb_strlen($v) > $max) {
            return ['error' => "{$field['label']}: use {$max} characters or fewer."];
        }
        if ($field['kind'] === 'list' && count(self::lines($v)) > PageTextData::LIST_MAX_ITEMS) {
            return ['error' => "{$field['label']}: use ".PageTextData::LIST_MAX_ITEMS.' points or fewer.'];
        }

        return ['value' => $v];
    }

    /**
     * Stored edits for one admin page, read in a single query, plus the page's
     * version token (changes whenever any of its texts is added, changed or removed).
     *
     * @return array{rows: array<string, string>, version: string}
     */
    public static function load(array $page): array
    {
        $rows = PageTextRow::query()->whereIn('key', self::keys($page))->pluck('value', 'key')->all();

        return ['rows' => $rows, 'version' => self::version($rows)];
    }

    /** Content-based token: sorted key=value pairs (independent of timestamp precision). */
    public static function version(array $rows): string
    {
        if (! $rows) {
            return 'none';
        }
        ksort($rows);

        return md5(json_encode($rows, JSON_UNESCAPED_UNICODE));
    }

    /** Form values: what the public site currently shows for each field. */
    public static function effective(array $page, array $rows): array
    {
        $out = [];
        foreach (self::keys($page) as $k) {
            $out[$k] = ($rows[$k] ?? '') !== '' ? $rows[$k] : PageTextData::DEFAULTS[$k];
        }

        return $out;
    }

    /** All stored edits for the public snapshot (empty before the table exists). */
    public static function overrides(): array
    {
        try {
            return PageTextRow::query()->pluck('value', 'key')->filter(fn ($v, $k) => $v !== '' && array_key_exists($k, PageTextData::DEFAULTS))->all();
        } catch (QueryException) {
            return []; // upgrade-2026-10-28.sql not imported yet: show the original wording
        }
    }
}
