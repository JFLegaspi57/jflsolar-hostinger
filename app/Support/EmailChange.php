<?php

namespace App\Support;

use App\Models\Member;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * A member changes the email address on their account. The new address only takes effect after the
 * member opens the confirmation link sent to it, so a mistyped address can never replace a working one.
 */
final class EmailChange
{
    private const TABLE = 'member_email_changes';

    private const TABLE_KEY = 'member-email-changes-table-v1';

    public const HOURS = 24;

    public static function ensureTable(): void
    {
        if (Cache::get(self::TABLE_KEY)) {
            return;
        }
        if (! Schema::hasTable(self::TABLE)) {
            Schema::create(self::TABLE, function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('member_id')->index();
                $t->string('new_email', 190);
                $t->string('token_hash', 64)->unique();
                $t->timestamp('created_at')->nullable();
            });
        }
        Cache::forever(self::TABLE_KEY, true);
    }

    /** Store a pending change and return the raw token for the confirmation link. Older pending changes are replaced. */
    public static function start(Member $member, string $newEmail): string
    {
        self::ensureTable();
        $token = Str::random(48);
        DB::table(self::TABLE)->where('member_id', $member->id)->delete();
        DB::table(self::TABLE)->insert([
            'member_id' => $member->id,
            'new_email' => mb_strtolower(trim($newEmail)),
            'token_hash' => hash('sha256', $token),
            'created_at' => now(),
        ]);

        return $token;
    }

    /** The pending address for this member, if any and not expired. */
    public static function pending(int $memberId): ?string
    {
        self::ensureTable();

        return DB::table(self::TABLE)->where('member_id', $memberId)->where('created_at', '>=', now()->subHours(self::HOURS))->value('new_email');
    }

    /**
     * Apply the change for a confirmation link. Returns the member on success, or null when the link is
     * unknown, expired, or the address now belongs to another account.
     */
    public static function confirm(string $token): ?Member
    {
        if (strlen($token) !== 48) {
            return null;
        }
        self::ensureTable();
        $row = DB::table(self::TABLE)->where('token_hash', hash('sha256', $token))->first();
        if (! $row) {
            return null;
        }
        DB::table(self::TABLE)->where('id', $row->id)->delete();
        if (\Illuminate\Support\Carbon::parse($row->created_at)->lt(now()->subHours(self::HOURS))) {
            return null;
        }
        $member = Member::query()->find($row->member_id);
        if (! $member || Member::query()->where('email', $row->new_email)->where('id', '!=', $member->id)->exists()) {
            return null;
        }
        $old = $member->email;
        $wasSubscribed = Newsletter::isSubscribed($old);
        $member->forceFill(['email' => $row->new_email])->save();
        if ($wasSubscribed) {
            Newsletter::unsubscribeEmail($old);
            Newsletter::subscribe($row->new_email, $member->name, 'account');
        }
        DB::table('member_password_resets')->where('email', $old)->delete();

        return $member;
    }
}
