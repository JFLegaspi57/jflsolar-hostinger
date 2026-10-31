<?php

namespace App\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Reads typed values from an admin form and collects staff-friendly field
 * errors. On failure the controller redirects back with every typed value
 * (old input) and these errors, so nothing the person typed is lost.
 */
final class FormParser
{
    public array $errors = [];

    public function __construct(private Request $request) {}

    public static function slugify(string $input): string
    {
        $s = trim(preg_replace('/[^a-z0-9]+/', '-', mb_strtolower(Str::ascii($input))), '-');

        return rtrim(substr($s, 0, 80), '-');
    }

    private function raw(string $name): string
    {
        $v = $this->request->input($name);

        return is_string($v) ? trim(str_replace(["\r\n", "\r"], "\n", $v)) : '';
    }

    public function text(string $name, string $label, int $max, bool $required = false, int $min = 0): ?string
    {
        $v = $this->raw($name);
        if ($v === '') {
            if ($required) {
                $this->errors[$name] = "{$label} is required.";
            }

            return null;
        }
        $len = mb_strlen($v);
        if ($min && $len < $min) {
            $this->errors[$name] = "{$label} must be at least {$min} characters.";
        }
        if ($len > $max) {
            $this->errors[$name] = "{$label} must be {$max} characters or fewer (currently {$len}).";
        }

        return $v;
    }

    /** Text stored as '' when empty (column has an empty-string default). */
    public function body(string $name, string $label, int $max): string
    {
        return $this->text($name, $label, $max) ?? '';
    }

    public function slug(string $name, ?string $fallbackFrom): ?string
    {
        $v = $this->raw($name) ?: ($fallbackFrom ? self::slugify($fallbackFrom) : '');
        if ($v === '') {
            // Nothing to derive it from: the missing name is reported instead.
            if ($fallbackFrom) {
                $this->errors[$name] = 'Could not create a web address from the name. Enter one, e.g. hybrid-6kw-system.';
            }

            return null;
        }
        if (! preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $v) || strlen($v) > 80) {
            $this->errors[$name] = 'Use lowercase letters, numbers and single hyphens only (e.g. hybrid-6kw-system).';

            return null;
        }

        return $v;
    }

    public function sku(string $name): ?string
    {
        $v = strtoupper($this->raw($name));
        if ($v === '') {
            return null;
        }
        if (! preg_match('/^[A-Z0-9][A-Z0-9-]{1,39}$/', $v)) {
            $this->errors[$name] = 'SKU: 2–40 characters, letters, numbers and hyphens.';
        }

        return $v;
    }

    public function number(string $name, string $label, float $max, float $min = 0, bool $required = false, bool $positive = false): ?float
    {
        $v = preg_replace('/[,₱\s]/u', '', $this->raw($name));
        if ($v === '') {
            if ($required) {
                $this->errors[$name] = "{$label} is required.";
            }

            return null;
        }
        if (! is_numeric($v)) {
            $this->errors[$name] = "{$label} must be a number.";

            return null;
        }
        $n = (float) $v;
        if ($positive && $n <= 0) {
            $this->errors[$name] = "{$label} must be greater than zero.";
        } elseif ($n < $min || $n > $max) {
            $this->errors[$name] = "{$label} must be between {$min} and {$max}.";
        } else {
            return round($n, 2);
        }

        return null;
    }

    public function int(string $name, string $label, int $min, int $max): ?int
    {
        $n = $this->number($name, $label, $max, $min);

        return $n === null ? null : (int) $n;
    }

    public function bool(string $name): bool
    {
        return in_array($this->request->input($name), ['on', 'true', '1', 1, true], true);
    }

    public function choice(string $name, string $label, array $allowed, bool $required = false): ?string
    {
        $v = $this->raw($name);
        if ($v === '') {
            if ($required) {
                $this->errors[$name] = 'Choose '.mb_strtolower($label).'.';
            }

            return null;
        }
        if (! in_array($v, $allowed, true)) {
            $this->errors[$name] = 'Choose a valid '.mb_strtolower($label).'.';

            return null;
        }

        return $v;
    }

    public function date(string $name, string $label): ?string
    {
        $v = $this->raw($name);
        if ($v === '') {
            return null;
        }
        $d = \DateTime::createFromFormat('!Y-m-d', $v);
        if (! $d || $d->format('Y-m-d') !== $v) {
            $this->errors[$name] = "{$label} must be a valid date.";

            return null;
        }

        return $v;
    }

    /** One item per line (leading bullets removed). */
    public function lines(string $name, string $label, int $maxItems, int $maxLen): array
    {
        $items = array_values(array_filter(array_map(fn ($l) => trim(preg_replace('/^[-•*]\s*/u', '', $l)), explode("\n", $this->raw($name))), fn ($l) => $l !== ''));
        if (count($items) > $maxItems) {
            $this->errors[$name] = "{$label}: at most {$maxItems} lines.";
        }
        foreach ($items as $i) {
            if (mb_strlen($i) > $maxLen) {
                $this->errors[$name] = "{$label}: each line must be {$maxLen} characters or fewer.";
            }
        }

        return $items;
    }

    /** "Label: value" per line. */
    public function specs(string $name): array
    {
        $out = [];
        $lines = array_values(array_filter(array_map('trim', explode("\n", $this->raw($name))), fn ($l) => $l !== ''));
        foreach ($lines as $i => $line) {
            $idx = mb_strpos($line, ':');
            if ($idx === false || $idx === 0 || $idx === mb_strlen($line) - 1) {
                $this->errors[$name] = 'Line '.($i + 1).': use the format "Label: value".';

                continue;
            }
            $out[] = ['label' => mb_substr(trim(mb_substr($line, 0, $idx)), 0, 80), 'value' => mb_substr(trim(mb_substr($line, $idx + 1)), 0, 200)];
        }
        if (count($out) > 40) {
            $this->errors[$name] = 'At most 40 specification lines.';
        }

        return $out;
    }

    /** "Component | details | quantity" per line (quantity optional). */
    public function components(string $name): array
    {
        $out = [];
        $lines = array_values(array_filter(array_map('trim', explode("\n", $this->raw($name))), fn ($l) => $l !== ''));
        foreach ($lines as $i => $line) {
            $parts = array_map('trim', explode('|', $line));
            [$label, $detail, $qty, $products] = array_pad($parts, 4, '');
            if ($label === '' || $detail === '') {
                $this->errors[$name] = 'Line '.($i + 1).': use "Component | details | quantity", optionally followed by "| product-address, other-product-address".';

                continue;
            }
            // Optional 4th column: the products customers can buy for this component (web addresses
            // after /products/, comma separated, the first is the default). Turns on "Build your package".
            $slugs = array_values(array_unique(array_filter(array_map(fn ($x) => strtolower(trim(preg_replace('#^.*/products/#', '', trim($x)), " /\t")), explode(',', $products)))));
            foreach ($slugs as $slug) {
                if (! preg_match('/^[a-z0-9][a-z0-9-]{0,159}$/', $slug)) {
                    $this->errors[$name] = 'Line '.($i + 1).': "'.mb_substr($slug, 0, 60).'" is not a product web address (e.g. goodwe-gw6000-es-c10-hybrid-inverter).';
                }
            }
            if (count($slugs) > 8) {
                $this->errors[$name] = 'Line '.($i + 1).': at most 8 product choices per component.';
            }
            $row = ['label' => mb_substr($label, 0, 80), 'detail' => mb_substr($detail, 0, 200), 'quantity' => $qty !== '' ? mb_substr($qty, 0, 40) : null];
            if ($slugs) {
                $row['products'] = array_slice($slugs, 0, 8);
            }
            $out[] = $row;
        }
        if (count($out) > 20) {
            $this->errors[$name] = 'At most 20 components.';
        }

        return $out;
    }

    public function ok(): bool
    {
        return ! $this->errors;
    }
}
