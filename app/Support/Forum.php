<?php

namespace App\Support;

use App\Models\ForumPost;
use App\Models\ForumTopic;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Community forum: members ask and answer; staff reply as "JFL Solar team".
 * A member's posts wait for staff approval until one has been approved (then the
 * member is trusted and posts appear at once). Staff can hide, pin and lock.
 */
final class Forum
{
    private const TABLES_KEY = 'forum-tables-v1';

    public const CATEGORIES = [
        'solar-panels' => 'Solar panels',
        'inverters' => 'Inverters',
        'batteries' => 'Batteries',
        'installation' => 'Installation & wiring',
        'grid' => 'Net metering & grid',
        'general' => 'General',
    ];

    public const STAFF_NAME = 'JFL Solar team';

    public const MAX_LINKS = 3;

    public static function hasTables(): bool
    {
        return Schema::hasTable('forum_topics') && Schema::hasTable('forum_posts');
    }

    public static function ensureTables(): void
    {
        if (Cache::get(self::TABLES_KEY)) {
            return;
        }
        Members::ensureTables();
        try {
            if (! Schema::hasTable('forum_topics')) {
                Schema::create('forum_topics', function (Blueprint $t) {
                    $t->id();
                    $t->string('slug', 120)->unique();
                    $t->string('category', 30)->index();
                    $t->string('title', 140);
                    $t->unsignedBigInteger('member_id')->nullable()->index();
                    $t->unsignedBigInteger('staff_user_id')->nullable();
                    $t->string('author_name', 100);
                    $t->string('status', 10)->default('published');   // published | pending | hidden
                    $t->boolean('pinned')->default(false);
                    $t->boolean('locked')->default(false);
                    $t->unsignedInteger('replies_count')->default(0);
                    $t->timestamp('last_post_at')->nullable();
                    $t->timestamps();
                    $t->index(['status', 'pinned', 'last_post_at']);
                });
            }
            if (! Schema::hasTable('forum_posts')) {
                Schema::create('forum_posts', function (Blueprint $t) {
                    $t->id();
                    $t->foreignId('topic_id')->constrained('forum_topics')->cascadeOnDelete();
                    $t->boolean('is_opening')->default(false);
                    $t->unsignedBigInteger('member_id')->nullable()->index();
                    $t->unsignedBigInteger('staff_user_id')->nullable();
                    $t->string('author_name', 100);
                    $t->text('body');
                    $t->string('status', 10)->default('published')->index();
                    $t->timestamps();
                });
            }
        } catch (\Throwable $e) {
            if (! self::hasTables()) {
                throw $e;
            }
        }
        Cache::forever(self::TABLES_KEY, true);
    }

    public static function forgetTables(): void
    {
        Cache::forget(self::TABLES_KEY);
    }

    /** Staff signed in to the admin in this browser (they post as "JFL Solar team" and can moderate). */
    public static function staff(): ?User
    {
        $u = auth('web')->user();

        return $u && $u->active && ! $u->must_change_password ? $u : null;
    }

    /** Public name on posts: first name and last initial ("Maria Santos" → "Maria S."). */
    public static function displayName(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];
        $first = mb_substr($parts[0] ?? 'Member', 0, 40);
        $last = count($parts) > 1 ? mb_strtoupper(mb_substr(end($parts), 0, 1)).'.' : '';

        return trim($first.' '.$last);
    }

    /** Problem with a message body, or null. */
    public static function bodyProblem(string $body): ?string
    {
        if (mb_strlen($body) < 10) {
            return 'Please write at least 10 characters.';
        }
        if (mb_strlen($body) > 5000) {
            return 'Please keep your message under 5,000 characters.';
        }
        if (preg_match_all('#https?://#i', $body) > self::MAX_LINKS) {
            return 'Please include no more than '.self::MAX_LINKS.' links.';
        }

        return null;
    }

    /** Plain text → safe HTML: escaped, line breaks kept, web links clickable (nofollow). */
    public static function render(string $body): HtmlString
    {
        $html = e($body);
        $html = preg_replace_callback('#\bhttps?://[^\s<>"\']{2,300}#i', function ($m) {
            $url = rtrim($m[0], '.,;:!?)');
            $tail = substr($m[0], strlen($url));

            return '<a href="'.$url.'" rel="nofollow ugc noopener" target="_blank" class="underline break-all">'.$url.'</a>'.$tail;
        }, $html);

        return new HtmlString(nl2br($html, false));
    }

    public static function newSlug(string $title): string
    {
        $base = Str::slug(Str::limit($title, 70, '')) ?: 'topic';

        return $base.'-'.Str::lower(Str::random(5));
    }

    /** Status for a new post by this author: staff and trusted members publish at once. */
    public static function initialStatus(?Member $member): string
    {
        return $member && ! $member->forum_trusted ? 'pending' : 'published';
    }

    /** Recounts a topic's replies and latest activity, and follows its opening post's status. */
    public static function refreshTopic(int $topicId): void
    {
        $topic = ForumTopic::query()->find($topicId);
        if (! $topic) {
            return;
        }
        $opening = ForumPost::query()->where('topic_id', $topicId)->where('is_opening', true)->first();
        $published = ForumPost::query()->where('topic_id', $topicId)->where('status', 'published');
        $data = [
            'replies_count' => (clone $published)->where('is_opening', false)->count(),
            'last_post_at' => (clone $published)->max('created_at') ?? $topic->created_at,
        ];
        // A topic is visible when its opening message is (unless staff hid the whole topic).
        if ($opening && $topic->status !== 'hidden') {
            $data['status'] = $opening->status === 'published' ? 'published' : 'pending';
        }
        $topic->forceFill($data)->save();
    }

    public static function pendingCount(): int
    {
        return self::hasTables() ? ForumPost::query()->where('status', 'pending')->count() : 0;
    }
}
