<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Certificates of completion for the online course. Each one gets its own certificate number (CTN),
 * e.g. JFL-2026-0001-K7Q: year issued, a running number, and three random letters/digits.
 * Issued by staff in Admin → Members → (member); shown on the member's account page.
 */
final class CourseCertificates
{
    private const TABLE = 'course_certificates';

    private const TABLE_KEY = 'course-certificates-table-v1';

    public static function ensureTable(): void
    {
        if (Cache::get(self::TABLE_KEY)) {
            return;
        }
        if (! Schema::hasTable(self::TABLE)) {
            Schema::create(self::TABLE, function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('member_id')->unique();
                $t->string('ctn', 40)->unique();
                $t->date('completed_on')->nullable();
                $t->timestamp('issued_at');
                $t->timestamps();
            });
        }
        Cache::forever(self::TABLE_KEY, true);
    }

    public static function for(int $memberId): ?object
    {
        self::ensureTable();

        return DB::table(self::TABLE)->where('member_id', $memberId)->first();
    }

    /** Issues the member's certificate number (or returns the one they already have). */
    public static function issue(int $memberId, ?string $completedOn = null): object
    {
        self::ensureTable();
        if ($have = self::for($memberId)) {
            return $have;
        }

        return DB::transaction(function () use ($memberId, $completedOn) {
            $id = DB::table(self::TABLE)->insertGetId([
                'member_id' => $memberId, 'ctn' => 'pending-'.$memberId.'-'.bin2hex(random_bytes(6)),
                'completed_on' => $completedOn, 'issued_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';   // no 0/O or 1/I, so it can't be misread
            do {
                $tail = '';
                for ($i = 0; $i < 3; $i++) {
                    $tail .= $chars[random_int(0, strlen($chars) - 1)];
                }
                $ctn = 'JFL-'.now('Asia/Manila')->format('Y').'-'.str_pad((string) $id, 4, '0', STR_PAD_LEFT).'-'.$tail;
            } while (DB::table(self::TABLE)->where('ctn', $ctn)->exists());
            DB::table(self::TABLE)->where('id', $id)->update(['ctn' => $ctn]);

            return DB::table(self::TABLE)->where('id', $id)->first();
        });
    }

    public static function revoke(int $memberId): void
    {
        self::ensureTable();
        DB::table(self::TABLE)->where('member_id', $memberId)->delete();
    }
}
