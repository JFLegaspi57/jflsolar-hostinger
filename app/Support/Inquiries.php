<?php

namespace App\Support;

use Illuminate\Support\Facades\Crypt;

/** Inquiry form rules shared by the form view and the controller. */
final class Inquiries
{
    public const LIMITS = ['name' => 100, 'email' => 254, 'phone' => 30, 'message' => 3000, 'location' => 120];

    public const MESSAGE_MIN = 10;

    public const MIN_FILL_SECONDS = 3;

    public const TOKEN_TTL_SECONDS = 12 * 60 * 60;

    public const CLIENT_LIMIT = 5;

    public const CLIENT_WINDOW_SECONDS = 600;

    /** Field order for the error summary, and the element each error links to. */
    public const FIELD_TARGET = [
        'item' => 'item-remove',
        'name' => 'f-name',
        'contact' => 'f-phone',
        'phone' => 'f-phone',
        'email' => 'f-email',
        'inquiry_type' => 'f-type',
        'location' => 'f-location',
        'bill_range' => 'f-bill',
        'message' => 'f-message',
    ];

    /** Signed, encrypted render time: proves the form came from us and enforces a minimum fill time. */
    public static function issueToken(?int $at = null): string
    {
        return Crypt::encryptString((string) ($at ?? time()));
    }

    /** @return 'ok'|'invalid'|'too_fast'|'expired' */
    public static function checkToken(?string $token): string
    {
        try {
            $issued = (int) Crypt::decryptString((string) $token);
        } catch (\Throwable) {
            return 'invalid';
        }
        $age = time() - $issued;
        if ($age < self::MIN_FILL_SECONDS) {
            return 'too_fast';
        }

        return $age > self::TOKEN_TTL_SECONDS ? 'expired' : 'ok';
    }

    /** Collapse control characters and whitespace, trim, cap to the field limit. */
    public static function clean(mixed $raw, int $max, bool $multiline = false): string
    {
        if (! is_string($raw)) {
            return '';
        }
        $s = $multiline
            ? preg_replace('/[^\S\n]+/u', ' ', str_replace(["\r\n", "\r"], "\n", $raw))
            : preg_replace('/\s+/u', ' ', $raw);
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) $s);

        return mb_substr(trim((string) $s), 0, $max);
    }

    public static function read(array $input): array
    {
        $itemType = $input['item_type'] ?? '';

        return [
            'name' => self::clean($input['name'] ?? null, self::LIMITS['name']),
            'email' => mb_strtolower(self::clean($input['email'] ?? null, self::LIMITS['email'])),
            'phone' => self::clean($input['phone'] ?? null, self::LIMITS['phone']),
            'inquiry_type' => self::clean($input['inquiry_type'] ?? null, 40),
            'message' => self::clean($input['message'] ?? null, self::LIMITS['message'], true),
            'location' => self::clean($input['location'] ?? null, self::LIMITS['location']),
            'bill_range' => self::clean($input['bill_range'] ?? null, 20),
            'item_type' => in_array($itemType, ['package', 'product'], true) ? $itemType : '',
            'item_slug' => self::clean($input['item_slug'] ?? null, 80),
            'item_name' => self::clean($input['item_name'] ?? null, 160),
        ];
    }

    /** Validates only what was supplied; at least one usable contact method is required. */
    public static function validate(array $v): array
    {
        $e = [];
        if (mb_strlen($v['name']) < 2) {
            $e['name'] = 'Enter your name.';
        }
        if ($v['email'] !== '' && ! preg_match('/^[^\s@<>()[\],;:"]+@[^\s@<>()[\],;:"]+\.[a-z]{2,}$/i', $v['email'])) {
            $e['email'] = 'Enter a valid email address, like name@example.com.';
        }
        if ($v['phone'] !== '') {
            $digits = strlen(preg_replace('/\D/', '', $v['phone']));
            if (! preg_match('/^[0-9+()\-.\s]+$/', $v['phone']) || $digits < 7 || $digits > 15) {
                $e['phone'] = 'Enter a valid phone number, like 0917 123 4567.';
            }
        }
        if ($v['email'] === '' && $v['phone'] === '') {
            $e['contact'] = 'Give us a phone number or an email address so we can reply.';
        }
        if (! array_key_exists($v['inquiry_type'], Labels::INQUIRY_TYPES)) {
            $e['inquiry_type'] = 'Choose what your inquiry is about.';
        }
        if (mb_strlen($v['message']) < self::MESSAGE_MIN) {
            $e['message'] = 'Tell us a little more (at least '.self::MESSAGE_MIN.' characters).';
        }
        if ($v['bill_range'] !== '' && ! array_key_exists($v['bill_range'], Labels::BILL_RANGES)) {
            $e['bill_range'] = 'Choose a bill range from the list.';
        }
        if ($v['item_slug'] !== '' && ($v['item_type'] === '' || ! preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $v['item_slug']))) {
            $e['item'] = 'The selected item is not valid. Remove it and try again.';
        }

        return $e;
    }

    /** Salted one-way fingerprint of the client IP for rate limiting (never stored with the inquiry). */
    public static function clientKey(string $ip): string
    {
        return 'inquiry:'.hash_hmac('sha256', 'ip|'.$ip, (string) config('app.key'));
    }
}
