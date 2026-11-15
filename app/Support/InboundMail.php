<?php

namespace App\Support;

use App\Models\Inquiry;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

/**
 * Emails to info@jflsolar.ph (and its sales@ alias) become inquiries in the admin.
 *
 * Hostinger Agentic Mail calls POST /webhooks/hostinger-mail when a message lands in the
 * mailbox ("message.received"), with "Authorization: Bearer <webhook secret>". Staff paste
 * that secret once in Admin → Inquiries; only its SHA-256 hash is stored. The payload has
 * {id, event, timestamp, data: {from, to[], subject, plainBody (first 200 characters),
 * htmlBody, bodyUrl (full body, signed link), attachments[]}}. The full text is fetched from
 * bodyUrl when possible; attachments are listed by name (their links expire after a day).
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
            'text' => mb_substr($first(['plainBody', 'text', 'snippet', 'preview', 'body', 'content']) ?? self::htmlToText((string) ($first(['htmlBody', 'html']) ?? '')), 0, 8000),
            'to' => array_values(array_filter(array_map(fn ($t) => is_string($t) ? $t : (is_array($t) ? ($t['address'] ?? $t['email'] ?? null) : null), (array) (data_get($m, 'to') ?? [])))),
            'bodyUrl' => is_string($u = data_get($m, 'bodyUrl')) && str_starts_with($u, 'https://') ? $u : null,
            'attachments' => array_values(array_filter(array_map(fn ($a) => is_array($a) ? ($a['filename'] ?? null) : null, (array) (data_get($m, 'attachments') ?? [])))),
            'id' => is_string($id = $payload['id'] ?? data_get($m, 'eventId')) ? $id : null,
        ];
    }

    /** The full body from Hostinger's signed link (plain text, HTML or a raw email); null if it can't be read. */
    public static function fetchBody(string $url): ?string
    {
        try {
            $res = Http::timeout(8)->accept('*/*')->get($url);
        } catch (\Throwable) {
            return null;
        }
        if (! $res->successful()) {
            return null;
        }
        $body = (string) $res->body();
        if ($body === '') {
            return null;
        }
        $type = strtolower((string) $res->header('Content-Type'));
        $json = str_contains($type, 'json') ? json_decode($body, true) : null;
        if (is_array($json)) {
            $body = (string) ($json['plainBody'] ?? $json['text'] ?? $json['body'] ?? self::htmlToText((string) ($json['htmlBody'] ?? $json['html'] ?? '')));
        } elseif (preg_match('/^(Received|Return-Path|MIME-Version|From|Delivered-To|Content-Type):/mi', substr($body, 0, 2000))) {
            $body = self::mimeText($body);
        } elseif (str_contains($type, 'html') || preg_match('/<(html|body|div|p|br)\b/i', substr($body, 0, 2000))) {
            $body = self::htmlToText($body);
        }
        $body = trim(preg_replace("/\n{3,}/", "\n\n", str_replace(["\r\n", "\r"], "\n", $body)));

        return $body !== '' ? mb_substr($body, 0, 8000) : null;
    }

    /** Plain text of a raw email: the first text/plain part (else text/html), decoded. */
    public static function mimeText(string $raw): string
    {
        $raw = str_replace("\r\n", "\n", $raw);
        $best = null;
        foreach (preg_split('/\n--[^\n]+\n/', $raw) as $part) {
            [$head, $content] = array_pad(explode("\n\n", $part, 2), 2, '');
            if (! preg_match('#content-type:\s*text/(plain|html)#i', $head, $t)) {
                continue;
            }
            if (preg_match('/content-transfer-encoding:\s*quoted-printable/i', $head)) {
                $content = quoted_printable_decode($content);
            } elseif (preg_match('/content-transfer-encoding:\s*base64/i', $head)) {
                $content = (string) base64_decode(preg_replace('/\s+/', '', $content));
            }
            if (preg_match('/charset="?([\w-]+)/i', $head, $cs) && strcasecmp($cs[1], 'utf-8') !== 0) {
                $content = (string) @mb_convert_encoding($content, 'UTF-8', $cs[1]);
            }
            $text = strtolower($t[1]) === 'html' ? self::htmlToText($content) : $content;
            if (strtolower($t[1]) === 'plain') {
                return $text;
            }
            $best ??= $text;
        }

        return $best ?? '';
    }

    public static function htmlToText(string $html): string
    {
        $html = preg_replace('#<(script|style)\b.*?</\1>#is', '', $html);
        $html = preg_replace('#<br\s*/?>|</(p|div|li|tr|h\d)>#i', "\n", (string) $html);

        return trim(html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /** Save an email as a new inquiry. Returns null when skipped (no sender, own domain, or a repeat delivery). */
    public static function store(array $payload): ?Inquiry
    {
        $p = self::parse($payload);
        if (! $p['email'] || str_ends_with($p['email'], '@'.self::OWN_DOMAIN)) {
            return null;
        }
        // Hostinger may deliver the same event again (same id): handle it once.
        if ($p['id'] && ! Cache::add('mail-webhook:'.$p['id'], 1, now()->addDays(2))) {
            return null;
        }
        $text = ($p['bodyUrl'] ? self::fetchBody($p['bodyUrl']) : null) ?? $p['text'];
        $toSales = collect($p['to'])->contains(fn ($t) => str_contains(mb_strtolower($t), 'sales@'.self::OWN_DOMAIN));
        $message = trim(
            ($toSales ? "To: sales@jflsolar.ph\n" : '')
            .($p['subject'] !== '' ? 'Subject: '.$p['subject']."\n\n" : ($toSales ? "\n" : ''))
            .($text !== '' ? $text : '(No text in the email. Open it in the info@jflsolar.ph inbox.)')
            .($p['attachments'] ? "\n\nAttachments (open them in the inbox): ".implode(', ', $p['attachments']) : '')
        );
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
