<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Buckny's conversations with visitors, for staff to review in Admin → Buckny chats.
 *
 * One row per chat (the browser sends a random chat id; "Start a new chat" makes a new one).
 * The whole transcript is saved after each answer. No IP address is stored: visitors are
 * grouped by a keyed hash of their IP so staff can tell chats from the same person apart.
 * Chats older than KEEP_DAYS are deleted automatically (also stated in the privacy policy).
 */
final class ChatLog
{
    public const KEEP_DAYS = 90;

    private const TABLE = 'chat_conversations';

    /** Created on first use (Hostinger does not run migrations). */
    public static function ensureTable(): void
    {
        if (\Illuminate\Support\Facades\Cache::get('chat-log-table-v2')) {
            return;
        }
        if (! Schema::hasTable(self::TABLE)) {
            Schema::create(self::TABLE, function (Blueprint $t) {
                $t->id();
                $t->string('cid', 64)->unique();
                $t->string('visitor', 16)->index();
                $t->string('page', 200)->nullable();
                $t->string('lang', 5)->default('en');
                $t->string('device', 10)->nullable();
                $t->longText('messages');
                $t->unsignedInteger('questions')->default(0);
                $t->boolean('callback')->default(false);
                $t->timestamp('last_at')->index();
                $t->timestamps();
            });
        }
        // When staff last opened the chat: newer messages make it "new" again (Admin badge and pop-up).
        if (! Schema::hasColumn(self::TABLE, 'seen_at')) {
            Schema::table(self::TABLE, fn (Blueprint $t) => $t->timestamp('seen_at')->nullable());
            // Chats from before this was added count as read, except the last 24 hours.
            DB::table(self::TABLE)->where('last_at', '<', now()->subDay())->update(['seen_at' => DB::raw('last_at')]);
        }
        \Illuminate\Support\Facades\Cache::forever('chat-log-table-v2', true);
    }

    /** Chats staff haven't opened yet, or with new messages since they did. */
    public static function unseen(): \Illuminate\Database\Query\Builder
    {
        return self::query()->where(fn ($w) => $w->whereNull('seen_at')->orWhereColumn('last_at', '>', 'seen_at'));
    }

    public static function newCount(): int
    {
        try {
            return self::unseen()->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    public static function isNew(object $row): bool
    {
        return ! $row->seen_at || (string) $row->last_at > (string) $row->seen_at;
    }

    public static function markSeen(?int $id = null): void
    {
        $q = self::query();
        if ($id) {
            $q->where('id', $id);
        }
        $q->update(['seen_at' => now()]);
    }

    /** A chat id from the browser, or a fresh one when it is missing or malformed. */
    public static function chatId(mixed $cid): string
    {
        return is_string($cid) && preg_match('/^[A-Za-z0-9-]{8,64}$/', $cid) ? $cid : bin2hex(random_bytes(16));
    }

    /**
     * Save the chat so far plus Buckny's latest answer. Never breaks the chat itself.
     *
     * @param  list<array{role: string, content: string}>  $messages
     */
    public static function record(string $cid, array $messages, string $reply, bool $callback, string $ip, ?string $page, string $lang, ?string $userAgent): void
    {
        try {
            self::ensureTable();
            $now = now();
            $page = is_string($page) && str_starts_with($page, '/') ? mb_substr($page, 0, 200) : null;
            $row = DB::table(self::TABLE)->where('cid', $cid)->first();
            // The browser sends only the latest messages of a long chat: keep the earlier ones.
            if ($row) {
                $whole = array_merge(self::messages($row), [end($messages)]);
                if (count($whole) > count($messages) && array_slice($whole, -count($messages)) == $messages) {
                    $messages = $whole;
                }
            }
            $messages[] = ['role' => 'assistant', 'content' => $reply];
            $data = [
                'visitor' => substr(hash_hmac('sha256', $ip, (string) config('app.key')), 0, 16),
                'lang' => self::detectLang($messages, $lang),
                'device' => self::device($userAgent),
                'messages' => json_encode(array_values($messages), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
                'questions' => count(array_filter($messages, fn ($m) => $m['role'] === 'user')),
                'callback' => $callback || ($row->callback ?? false),
                'last_at' => $now,
                'updated_at' => $now,
            ];
            if ($row) {
                DB::table(self::TABLE)->where('id', $row->id)->update($data);
            } else {
                DB::table(self::TABLE)->insert($data + ['cid' => $cid, 'page' => $page, 'created_at' => $now]);
            }
            if (random_int(1, 50) === 1) {
                self::prune();
            }
        } catch (\Throwable $e) {
            Log::warning('Chat log not saved: '.$e->getMessage());
        }
    }

    public static function prune(): int
    {
        self::ensureTable();

        return DB::table(self::TABLE)->where('last_at', '<', now()->subDays(self::KEEP_DAYS))->delete();
    }

    public static function query(): \Illuminate\Database\Query\Builder
    {
        self::ensureTable();

        return DB::table(self::TABLE);
    }

    /** @return list<array{role: string, content: string}> */
    public static function messages(object $row): array
    {
        $list = json_decode((string) $row->messages, true);

        return is_array($list) ? array_values(array_filter($list, fn ($m) => is_array($m) && isset($m['role'], $m['content']))) : [];
    }

    public static function firstQuestion(object $row): string
    {
        foreach (self::messages($row) as $m) {
            if ($m['role'] === 'user') {
                return (string) $m['content'];
            }
        }

        return '';
    }

    public static function todayCount(): int
    {
        try {
            return self::query()->where('created_at', '>=', now('Asia/Manila')->startOfDay()->utc())->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    /** "fil" once the visitor writes in Filipino, else the site language. */
    private static function detectLang(array $messages, string $lang): string
    {
        $words = '/\b(ako|ang|ng|mga|po|opo|magkano|paano|ano|ba|naman|kung|ito|yung|sa|na|at|hindi|gusto|pwede|puwede|salamat|meron|wala|tayo|kayo|namin|ninyo)\b/iu';
        foreach ($messages as $m) {
            if ($m['role'] === 'user' && preg_match_all($words, (string) $m['content']) >= 2) {
                return 'fil';
            }
        }

        return $lang === 'fil' ? 'fil' : 'en';
    }

    private static function device(?string $ua): ?string
    {
        if (! $ua) {
            return null;
        }

        return preg_match('/iPad|Tablet/i', $ua) ? 'Tablet' : (preg_match('/Mobi|Android|iPhone/i', $ua) ? 'Mobile' : 'Computer');
    }
}
