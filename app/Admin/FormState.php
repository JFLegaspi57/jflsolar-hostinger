<?php

namespace App\Admin;

use Illuminate\Support\ViewErrorBag;

/**
 * Field values and errors for one admin form. After a failed save the
 * controller redirects back with old input, which takes precedence over the
 * stored record, so everything the person typed is restored.
 */
final class FormState
{
    private bool $hasOld;

    public function __construct(private array $values, private ?ViewErrorBag $errors = null)
    {
        $this->hasOld = session()->hasOldInput();
    }

    public function value(string $name): string
    {
        if ($this->hasOld) {
            $old = old($name);

            return is_string($old) ? $old : '';
        }
        $v = $this->values[$name] ?? '';

        return is_bool($v) ? ($v ? 'on' : '') : (string) $v;
    }

    public function checked(string $name): bool
    {
        return in_array($this->value($name), ['on', 'true', '1'], true);
    }

    public function error(string $name): ?string
    {
        return $this->errors?->first($name) ?: null;
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return collect($this->errors?->getMessages() ?? [])->map(fn ($m) => $m[0])->all();
    }

    /** True after a failed submission (the form holds unsaved values). */
    public function failed(): bool
    {
        return $this->hasOld;
    }
}
