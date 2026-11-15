<?php

namespace App\Support;

use App\Models\Inquiry;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Emails to info@jflsolar.ph (and its sales@ alias) become inquiries in the admin.
 *
 * Hostinger Agentic Mail calls POST /webhooks/hostinger-mail when a message lands in the
 * mailbox ("message.received"), with "Authorization: Bearer <webhook secret>". Staff paste
 * that secret once in Admin → Inquiries; only its SHA-256 hash is stored. The payload has
 * the sender, subject and a shortened copy of the message, so the full email stays in the
 * mailbox. Field names are read loosely because Hostinger documents them only by example.
 */
final class InboundMail
{
    public const TYPE = 'email';

    public const TYPE_LABEL = 'Email to info@jflsolar.ph';

    /** Senders on our own domain are skipped (site notifications, the sales@ alias), to avoid loops. */
    public const OWN_DOMAIN = 'jflsolar.ph';

    private const KEY = 'mail_webhook_hash';

    /** Small key/value table for site secrets (created on first use; Hostinger does not run migrations). */
    public static function ensureTable(): void
    {
        if (! Schema::hasTable('app_secrets')) {
            Schema::create('app_secrets', function (Blueprint $t) {
                $t->string('key', 60)->primary();
                $t->text('value');
                $t->timestamps();
            });
        }
    }

    public static function configured(): bool
    {
        self::ensureTable();

        return DB::table('app_secrets')->where('key', self::KEY)->exists();
    }

    public static function setSecret(string $secret): void
    {
        self::ensureTable();
        DB::table('app_secrets')->updateOrInsert(['key' => self::KEY], ['value' => hash('sha256', trim($secret)), 'updated_at' => now(), 'created_at' => now()]);
    }

    public static function forgetSecret(): void
    {
        self::ensureTable();
        DB::table('app_secrets')->where('key', self::KEY)->delete();
    }

    /** True when the request's bearer token matches the saved secret. */
    public static function authorized(?string $authorization): bool
    {
        self::ensureTable();
        $hash = DB::table('app_secrets')->where('key', self::KEY)->value('value');
        if (! $hash || ! preg_match('/^Bearer\s+(\S+)$/i', trim((string) $authorization), $m)) {
            return false;
        }

        return hash_equals((string) $hash, hash('sha256', $m[1]));
    }

    /**
     * Pull sender, subject and text out of a webhook payload.
     *
     * @return array{email: ?string, name: ?string, subject: string, text: string}
     */
    public static function parse(array $payload): array
    {
        $m = is_array($payload['message'] ?? null) ? $payload['message'] : (is_array($payload['data'] ?? null) ? $payload['data'] : $payload);
        $first = function (array $keys) use ($m, $payload) {
            foreach ($keys as $k) {
                foreach ([$m, $payload] as $src) {
                    $v = data_get($src, $k);
                    if (is_string($v) && trim($v) !== '') {
                        return trim($v);
                    }
                }
            }

            return null;
        };
        $fromRaw = data_get($m, 'from') ?? data_get($payload, 'from') ?? data_get($m, 'sender');
        if (is_array($fromRaw) && array_is_list($fromRaw)) {
            $fromRaw = $fromRaw[0] ?? null;
        }
        $email = $name = null;
        if (is_array($fromRaw)) {
            $email = $fromRaw['address'] ?? $fromRaw['email'] ?? null;
            $name = $fromRaw['name'] ?? null;
        } elseif (is_string($fromRaw)) {
            if (preg_match('/^\s*"?([^"<]*?)"?\s*<([^>]+)>\s*$/', $fromRaw, $x)) {
                [$name, $email] = [trim($x[1]), trim($x[2])];
            } else {
                $email = trim($fromRaw);
            }
        }
        $name = $name ?: $first(['from_name', 'sender_name']);
        $email = $email && filter_var($email, FILTER_VALIDATE_EMAIL) ? mb_strtolower($email) : null;

        return [
            'email' => $email,
            'name' => $name !== null && $name !== '' ? mb_substr($name, 0, 100) : null,
            'subject' => mb_substr($first(['subject', 'title']) ?? '', 0, 300),
            'text' => mb_substr($first(['text', 'snippet', 'preview', 'body_preview', 'truncated_message', 'truncated_body', 'body', 'content', 'plain']) ?? '', 0, 8000),
        ];
    }

    /** Save an email as a new inquiry. Returns null when skipped (no sender, own domain, or a repeat delivery). */
    public static function store(array $payload): ?Inquiry
    {
        $p = self::parse($payload);
        if (! $p['email'] || str_ends_with($p['email'], '@'.self::OWN_DOMAIN)) {
            return null;
        }
        $message = trim(($p['subject'] !== '' ? 'Subject: '.$p['subject']."\n\n" : '').($p['text'] !== '' ? $p['text'] : '(No text in the notification. Open the email in the info@jflsolar.ph inbox.)'));
        // Hostinger may deliver the same event again: skip an identical one from the last hour.
        $repeat = Inquiry::query()->where('inquiry_type', self::TYPE)->where('email', $p['email'])->where('message', $message)
            ->where('created_at', '>=', now()->subHour())->exists();
        if ($repeat) {
            return null;
        }

        return Inquiry::query()->create([
            'name' => $p['name'] ?: $p['email'],
            'email' => $p['email'],
            'inquiry_type' => self::TYPE,
            'message' => $message,
            'status' => 'new',
        ]);
    }
}
