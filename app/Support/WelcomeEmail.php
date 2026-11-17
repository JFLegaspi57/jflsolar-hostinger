<?php

namespace App\Support;

use App\Mail\MemberWelcome;
use App\Models\Member;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/**
 * The welcome email ("Thank you from the whole JFL Solar team + a quick tour of our website"),
 * sent automatically to every new member right after sign-up, from the website's own mailbox.
 * members.welcome_sent_at keeps track, so nobody gets it twice.
 */
final class WelcomeEmail
{
    /** Members who joined before this got the same email by hand from Gmail (or joined before it existed). */
    public const MANUAL_UNTIL = '2026-11-17 11:52:00';   // UTC (7:52 PM in Manila)

    private const KEY = 'member-welcome-column-v1';

    public static function ensureColumn(): void
    {
        if (Cache::get(self::KEY)) {
            return;
        }
        Members::ensureTables();
        if (! Schema::hasColumn('members', 'welcome_sent_at')) {
            Schema::table('members', fn (Blueprint $t) => $t->timestamp('welcome_sent_at')->nullable());
            DB::table('members')->where('created_at', '<', self::MANUAL_UNTIL)->update(['welcome_sent_at' => DB::raw('created_at')]);
        }
        Cache::forever(self::KEY, true);
    }

    /** Sends the welcome email to one member (once). Never breaks sign-up. */
    public static function send(Member $member): bool
    {
        try {
            self::ensureColumn();
            if (! Inquiries::mailReady() || ! filter_var($member->email, FILTER_VALIDATE_EMAIL)) {
                return false;
            }
            $claimed = DB::table('members')->where('id', $member->id)->whereNull('welcome_sent_at')->update(['welcome_sent_at' => now()]);
            if (! $claimed) {
                return false;   // already sent
            }
            try {
                Mail::to($member->email, $member->name)->send(new MemberWelcome($member));
                Cache::forget('member-welcome-last-error');
            } catch (\Throwable $e) {
                DB::table('members')->where('id', $member->id)->update(['welcome_sent_at' => null]);   // try again later
                Log::error('[welcome] email failed', ['member' => $member->id, 'error' => $e::class]);
                // For Admin → Subscribers: what went wrong (no passwords in mail-server messages).
                Cache::put('member-welcome-last-error', class_basename($e).': '.mb_substr(preg_replace('/\s+/', ' ', $e->getMessage()), 0, 300), now()->addDays(2));

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('[welcome] '.$e->getMessage());

            return false;
        }
    }

    /** Active members who haven't received it yet (for example while the mail server was down). */
    public static function pendingCount(): int
    {
        self::ensureColumn();

        return Member::query()->whereNull('welcome_sent_at')->where('status', 'active')->count();
    }

    /** Sends to waiting members, a batch at a time. @return int how many were sent */
    public static function sendPending(int $max = 10): int
    {
        self::ensureColumn();
        @set_time_limit(120);
        $sent = 0;
        foreach (Member::query()->whereNull('welcome_sent_at')->where('status', 'active')->orderBy('id')->limit($max)->get() as $m) {
            if (self::send($m)) {
                $sent++;
            } elseif (str_contains((string) self::lastError(), 'authenticate')) {
                break;   // the mail server refuses the website's login: no point trying the others now
            }
        }

        return $sent;
    }

    public static function lastError(): ?string
    {
        return Cache::get('member-welcome-last-error');
    }

    public static function sentSinceCount(): int
    {
        self::ensureColumn();

        return Member::query()->where('welcome_sent_at', '>=', self::MANUAL_UNTIL)->where('created_at', '>=', self::MANUAL_UNTIL)->count();
    }
}
