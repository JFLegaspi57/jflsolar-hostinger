<?php

namespace App\Support;

/** Shared class strings for buttons and badges (Blade has no component props for plain links). */
final class Ui
{
    private const BUTTON_BASE = 'inline-flex items-center justify-center gap-2 rounded-[var(--radius-control)] font-semibold transition-colors duration-150 disabled:cursor-not-allowed disabled:opacity-60 select-none';

    private const BUTTON_VARIANTS = [
        'primary' => 'bg-gold-500 text-steel-950 hover:bg-gold-400 active:bg-gold-600',
        'secondary' => 'border-2 border-steel-900 text-steel-900 hover:bg-steel-900 hover:text-white',
        'dark' => 'bg-steel-900 text-white hover:bg-steel-800',
        'ghost' => 'text-steel-900 underline-offset-4 hover:underline',
        'light' => 'border-2 border-white/80 text-white hover:bg-white hover:text-steel-950',
        'danger' => 'bg-danger-700 text-white hover:bg-[#93190f]',
    ];

    private const BUTTON_SIZES = [
        'sm' => 'min-h-11 px-3.5 text-sm',
        'md' => 'min-h-11 px-5 text-[0.95rem]',
        'lg' => 'min-h-12 px-6 text-base',
    ];

    public static function button(string $variant = 'primary', string $size = 'md', string $extra = ''): string
    {
        return trim(self::BUTTON_BASE.' '.self::BUTTON_VARIANTS[$variant].' '.self::BUTTON_SIZES[$size].' '.$extra);
    }

    public const BADGE_TONES = [
        'neutral' => 'bg-steel-100 text-steel-800 border-steel-200',
        'sample' => 'bg-white text-steel-800 border-steel-400 border-dashed',
        'pending' => 'bg-warn-50 text-warn-800 border-[#f0d39a]',
        'promo' => 'bg-gold-100 text-gold-800 border-gold-200',
        'ended' => 'bg-steel-100 text-steel-700 border-steel-300',
        'ok' => 'bg-ok-50 text-ok-700 border-[#b7e2c6]',
        'dark' => 'bg-steel-900 text-white border-steel-900',
        'danger' => 'bg-danger-50 text-danger-700 border-[#f3c3be]',
    ];

    /** Paragraphs from plain text separated by blank lines. */
    public static function paragraphs(?string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\n\s*\n/', (string) $text)), fn ($p) => $p !== ''));
    }

    /**
     * WebP twin of a /images/… JPEG srcset ("/images/a-640.jpg 640w, …"), or null when any
     * WebP file is missing (the page then just uses the JPEGs).
     */
    public static function webpSrcset(?string $srcset): ?string
    {
        if (! $srcset || ! str_contains($srcset, '.jpg')) {
            return null;
        }
        $out = preg_replace('/\.jpg(\s|$)/', '.webp$1', $srcset);
        foreach (preg_split('/\s*,\s*/', $out) as $part) {
            $path = strtok(trim($part), ' ');
            if (! str_starts_with($path, '/images/') || ! is_file(public_path($path))) {
                return null;
            }
        }

        return $out;
    }
}
