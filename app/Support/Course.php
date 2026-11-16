<?php

namespace App\Support;

use App\Models\Member;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * The online course "Solar PV System Design", for paid members only.
 *
 * Paid membership lasts one year (MEMBERSHIP_MONTHS) and is bought online through PayMongo
 * (GCash, Maya, card, QR Ph); the signed PayMongo webhook unlocks it, and staff can also set
 * or extend it by hand in Admin → Online course. Lesson text and slide images live only in
 * the database and in storage/app/course (never in the public folder or the code), and are
 * served only to paid members and signed-in staff.
 */
final class Course
{
    public const SLUG = 'solar-pv-system-design';

    public const TITLE = 'Solar PV System Design';

    public const PATH = '/courses/'.self::SLUG;

    public const MEMBERSHIP_MONTHS = 12;

    /** Membership payment references look like MEM-261116-AB12 (orders use JFL-…). */
    public const REF_PREFIX = 'MEM-';

    private const TABLES_KEY = 'course-tables-v1';

    // --- tables (created on first use; Hostinger runs no migrations) ---------------------

    public static function ensureTables(): void
    {
        if (Cache::get(self::TABLES_KEY)) {
            return;
        }
        Members::ensureTables();
        InboundMail::ensureTable();   // app_secrets: the membership price
        if (! Schema::hasColumn('members', 'paid_until')) {
            Schema::table('members', function (Blueprint $t) {
                $t->timestamp('paid_until')->nullable();
            });
        }
        if (! Schema::hasTable('course_lessons')) {
            Schema::create('course_lessons', function (Blueprint $t) {
                $t->id();
                $t->string('slug', 80)->unique();
                $t->string('title', 160);
                $t->string('summary', 400)->default('');   // shown to everyone in the lesson list
                $t->longText('body')->nullable();           // paid members only
                $t->integer('position')->default(0);
                $t->string('status', 10)->default('draft'); // draft | published
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('course_slides')) {
            Schema::create('course_slides', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('lesson_id')->index();
                $t->string('file', 60);
                $t->string('caption', 300)->default('');
                $t->integer('position')->default(0);
                $t->unsignedInteger('width')->default(0);
                $t->unsignedInteger('height')->default(0);
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('membership_payments')) {
            Schema::create('membership_payments', function (Blueprint $t) {
                $t->id();
                $t->string('reference', 30)->unique();
                $t->unsignedBigInteger('member_id')->index();
                $t->decimal('amount_php', 12, 2);
                $t->string('status', 10)->default('pending');   // pending | paid
                $t->string('checkout_id', 64)->nullable()->index();
                $t->string('payment_id', 64)->nullable();
                $t->string('method', 30)->nullable();
                $t->timestamp('paid_at')->nullable();
                $t->timestamps();
            });
        }
        Members::flushState();
        Cache::forever(self::TABLES_KEY, true);
    }

    public static function forgetTables(): void
    {
        Cache::forget(self::TABLES_KEY);
    }

    // --- membership ------------------------------------------------------------------------

    public static function paidUntil(?Member $member): ?Carbon
    {
        if (! $member) {
            return null;
        }
        self::ensureTables();
        $v = DB::table('members')->where('id', $member->id)->value('paid_until');

        return $v ? Carbon::parse($v) : null;
    }

    public static function isPaid(?Member $member): bool
    {
        $until = self::paidUntil($member);

        return $until !== null && $until->isFuture() && $member->isActive();
    }

    /** Paid members, and signed-in staff (to check the lessons). */
    public static function canView(): bool
    {
        return Forum::staff() !== null || self::isPaid(Members::current());
    }

    /** Adds one membership period, counted from today or from the current end date if later. */
    public static function extend(int $memberId, ?Carbon $until = null): Carbon
    {
        self::ensureTables();
        $current = DB::table('members')->where('id', $memberId)->value('paid_until');
        $from = $current && Carbon::parse($current)->isFuture() ? Carbon::parse($current) : now();
        $until ??= $from->copy()->addMonthsNoOverflow(self::MEMBERSHIP_MONTHS);
        DB::table('members')->where('id', $memberId)->update(['paid_until' => $until]);

        return $until;
    }

    public static function setUntil(int $memberId, ?Carbon $until): void
    {
        self::ensureTables();
        DB::table('members')->where('id', $memberId)->update(['paid_until' => $until]);
    }

    /** Membership price in pesos (set in Admin → Online course), or null when not set yet. */
    public static function price(): ?float
    {
        self::ensureTables();
        $v = DB::table('app_secrets')->where('key', 'course_price')->value('value');

        return is_numeric($v) && (float) $v > 0 ? (float) $v : null;
    }

    public static function setPrice(?float $price): void
    {
        self::ensureTables();
        if ($price === null) {
            DB::table('app_secrets')->where('key', 'course_price')->delete();

            return;
        }
        DB::table('app_secrets')->updateOrInsert(['key' => 'course_price'], ['value' => (string) round($price, 2), 'updated_at' => now(), 'created_at' => now()]);
    }

    /** Online enrolment is open: a price is set and PayMongo is set up. */
    public static function canBuy(): bool
    {
        return self::price() !== null && Shop::onlinePayment();
    }

    // --- lessons and slides ----------------------------------------------------------------

    /** @return \Illuminate\Support\Collection<int, object> */
    public static function lessons(bool $withDrafts = false): \Illuminate\Support\Collection
    {
        self::ensureTables();
        $q = DB::table('course_lessons')->orderBy('position')->orderBy('id');
        if (! $withDrafts) {
            $q->where('status', 'published');
        }

        return $q->get(['id', 'slug', 'title', 'summary', 'position', 'status', 'updated_at']);
    }

    public static function lesson(string $slug, bool $withDrafts = false): ?object
    {
        self::ensureTables();
        $q = DB::table('course_lessons')->where('slug', $slug);
        if (! $withDrafts) {
            $q->where('status', 'published');
        }

        return $q->first();
    }

    /** @return \Illuminate\Support\Collection<int, object> */
    public static function slides(int $lessonId): \Illuminate\Support\Collection
    {
        self::ensureTables();

        return DB::table('course_slides')->where('lesson_id', $lessonId)->orderBy('position')->orderBy('id')->get();
    }

    public static function dir(): string
    {
        return storage_path('app/course');
    }

    public static function slidePath(object $slide): string
    {
        return self::dir().'/'.$slide->lesson_id.'/'.$slide->file;
    }

    /**
     * Stores an uploaded image as a slide (resized to at most 1600 px wide, WebP).
     *
     * @return string|null an error message, or null when saved
     */
    public static function addSlide(int $lessonId, ?\Illuminate\Http\UploadedFile $file, string $caption = ''): ?string
    {
        if (! $file || ! $file->isValid()) {
            return 'The file did not upload. Please try again.';
        }
        if ($file->getSize() > 12 * 1024 * 1024) {
            return 'A slide image must be under 12 MB.';
        }
        $img = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
        if (! $img) {
            return 'Please upload JPG, PNG or WebP images.';
        }
        $w = imagesx($img);
        $h = imagesy($img);
        if ($w > 1600) {
            $nh = (int) round($h * 1600 / $w);
            $resized = imagecreatetruecolor(1600, $nh);
            imagecopyresampled($resized, $img, 0, 0, 0, 0, 1600, $nh, $w, $h);
            imagedestroy($img);
            [$img, $w, $h] = [$resized, 1600, $nh];
        }
        $dir = self::dir().'/'.$lessonId;
        if (! is_dir($dir) && ! @mkdir($dir, 0775, true)) {
            return 'The slide could not be stored.';
        }
        $name = bin2hex(random_bytes(10)).'.webp';
        $ok = imagewebp($img, $dir.'/'.$name, 82);
        imagedestroy($img);
        if (! $ok) {
            return 'The slide could not be stored.';
        }
        $pos = (int) DB::table('course_slides')->where('lesson_id', $lessonId)->max('position') + 1;
        DB::table('course_slides')->insert(['lesson_id' => $lessonId, 'file' => $name, 'caption' => mb_substr(trim($caption), 0, 300), 'position' => $pos, 'width' => $w, 'height' => $h, 'created_at' => now(), 'updated_at' => now()]);

        return null;
    }

    public static function deleteSlide(object $slide): void
    {
        @unlink(self::slidePath($slide));
        DB::table('course_slides')->where('id', $slide->id)->delete();
    }

    /** Lesson text: staff write light HTML; anything that could run code is removed. */
    public static function cleanHtml(?string $html): string
    {
        $html = (string) $html;
        $html = preg_replace('#<(script|style|iframe|object|embed|form|input|button|link|meta)\b[^>]*>.*?</\1>#is', '', $html);
        $html = preg_replace('#<(script|style|iframe|object|embed|form|input|button|link|meta)\b[^>]*/?>#i', '', (string) $html);
        $html = preg_replace('#\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', (string) $html);
        $html = preg_replace('#(href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\2#i', '$1="#"', (string) $html);

        return (string) $html;
    }

    /** Puts slide images in the lesson text where it says [[slide:N]] (N = slide number). */
    public static function renderBody(object $lesson, \Illuminate\Support\Collection $slides, string $watermark): string
    {
        $html = self::cleanHtml($lesson->body);
        $list = $slides->values();

        return (string) preg_replace_callback('/\[\[slide:(\d+)\]\]/', function ($m) use ($list, $watermark) {
            $s = $list[(int) $m[1] - 1] ?? null;
            if (! $s) {
                return '';
            }

            return view('pages.course.slide', ['slide' => $s, 'number' => (int) $m[1], 'watermark' => $watermark])->render();
        }, $html);
    }

    // --- paying online (PayMongo) ----------------------------------------------------------

    /**
     * Starts a PayMongo checkout for one year of paid membership.
     *
     * @return array{reference: string, url: string}
     */
    public static function startCheckout(Member $member): array
    {
        self::ensureTables();
        $price = self::price();
        if ($price === null || ! Shop::onlinePayment()) {
            throw new \RuntimeException('Online enrolment is not open.');
        }
        $ref = self::REF_PREFIX.now('Asia/Manila')->format('ymd').'-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
        $response = Http::withBasicAuth((string) config('shop.paymongo.secret_key'), '')
            ->acceptJson()->timeout(20)
            ->post((string) config('shop.paymongo.checkout_endpoint'), [
                'data' => ['attributes' => [
                    'line_items' => [[
                        'name' => 'JFL Solar paid membership, 1 year (Online course: '.self::TITLE.')',
                        'amount' => (int) round($price * 100),
                        'currency' => 'PHP',
                        'quantity' => 1,
                    ]],
                    'payment_method_types' => config('shop.paymongo.methods'),
                    'success_url' => Site::url(self::PATH.'?paid='.$ref),
                    'cancel_url' => Site::url(self::PATH.'?cancelled=1'),
                    'reference_number' => $ref,
                    'description' => 'JFL Solar paid membership '.$ref,
                    'send_email_receipt' => true,
                    'show_line_items' => true,
                    'billing' => array_filter(['name' => $member->name, 'email' => $member->email, 'phone' => $member->phone]),
                ]],
            ]);
        $id = (string) $response->json('data.id', '');
        $url = (string) $response->json('data.attributes.checkout_url', '');
        if (! $response->successful() || $id === '' || ! str_starts_with($url, 'https://')) {
            throw new \RuntimeException('PayMongo checkout failed: HTTP '.$response->status().' '.mb_substr((string) $response->json('errors.0.detail', ''), 0, 200));
        }
        DB::table('membership_payments')->insert(['reference' => $ref, 'member_id' => $member->id, 'amount_php' => $price, 'status' => 'pending', 'checkout_id' => $id, 'created_at' => now(), 'updated_at' => now()]);

        return ['reference' => $ref, 'url' => $url];
    }

    /**
     * Records a paid PayMongo session for a membership (from the signed webhook, or from asking
     * PayMongo directly). Safe to call twice. Returns true when the membership is (now) paid.
     *
     * @param  list<array{id: string, status: string, amount: float, method: string}>  $payments
     */
    public static function recordPayment(string $reference, string $sessionId, array $payments): bool
    {
        self::ensureTables();
        $row = DB::table('membership_payments')->where('reference', $reference)->first();
        if (! $row || ($row->checkout_id && $sessionId !== '' && $row->checkout_id !== $sessionId)) {
            Log::warning('[course] membership payment not matched', ['reference' => $reference]);

            return false;
        }
        if ($row->status === 'paid') {
            return true;
        }
        $paid = collect($payments)->firstWhere('status', 'paid');
        if (! $paid || $paid['amount'] + 0.005 < (float) $row->amount_php) {
            return false;
        }
        $done = DB::table('membership_payments')->where('id', $row->id)->where('status', 'pending')
            ->update(['status' => 'paid', 'payment_id' => mb_substr($paid['id'], 0, 64), 'method' => mb_substr($paid['method'], 0, 30), 'paid_at' => now(), 'updated_at' => now()]);
        if ($done) {
            self::extend((int) $row->member_id);
        }

        return true;
    }

    /** Backup when the webhook is late: ask PayMongo about the member's pending payment. */
    public static function confirmPending(string $reference, int $memberId): bool
    {
        self::ensureTables();
        $row = DB::table('membership_payments')->where('reference', $reference)->where('member_id', $memberId)->first();
        if (! $row) {
            return false;
        }
        if ($row->status === 'paid') {
            return true;
        }
        $s = $row->checkout_id ? PayMongo::fetchSession($row->checkout_id) : null;
        if (! $s) {
            return false;
        }

        return $s['reference'] === $reference && self::recordPayment($reference, $s['session_id'], $s['payments']);
    }
}
