<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Personal messages from JFL Solar to one member, shown on that member's account page until they close it.
 * Written in Admin → Members → (member).
 */
final class MemberMessages
{
    private const TABLE = 'member_messages';

    private const TABLE_KEY = 'member-messages-table-v1';

    public static function ensureTable(): void
    {
        if (Cache::get(self::TABLE_KEY)) {
            return;
        }
        if (! Schema::hasTable(self::TABLE)) {
            Schema::create(self::TABLE, function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('member_id')->index();
                $t->string('title', 200);
                $t->text('body');
                $t->timestamp('closed_at')->nullable();
                $t->timestamps();
            });
        }
        Cache::forever(self::TABLE_KEY, true);
    }

    public static function add(int $memberId, string $title, string $body): void
    {
        self::ensureTable();
        DB::table(self::TABLE)->insert(['member_id' => $memberId, 'title' => mb_substr(trim($title), 0, 200), 'body' => mb_substr(trim($body), 0, 10000), 'created_at' => now(), 'updated_at' => now()]);
    }

    /** Messages the member hasn't closed yet, newest first. */
    public static function open(int $memberId): Collection
    {
        self::ensureTable();

        return DB::table(self::TABLE)->where('member_id', $memberId)->whereNull('closed_at')->orderByDesc('id')->get();
    }

    /** Every message to the member, for staff, newest first. */
    public static function all(int $memberId): Collection
    {
        self::ensureTable();

        return DB::table(self::TABLE)->where('member_id', $memberId)->orderByDesc('id')->get();
    }

    /** The member closes one of their own messages. */
    public static function close(int $memberId, int $id): void
    {
        self::ensureTable();
        DB::table(self::TABLE)->where('id', $id)->where('member_id', $memberId)->whereNull('closed_at')->update(['closed_at' => now(), 'updated_at' => now()]);
    }

    public static function delete(int $memberId, int $id): void
    {
        self::ensureTable();
        DB::table(self::TABLE)->where('id', $id)->where('member_id', $memberId)->delete();
    }
}
