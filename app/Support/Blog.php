<?php

namespace App\Support;

use App\Models\BlogPost;
use App\Models\MediaAsset;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * JF Legaspi's blog: posts written and published by staff in Admin → Blog,
 * shown at /blog and linked from the founder section of the About page.
 * On Hostinger the table is created on first use (no migrations there).
 */
final class Blog
{
    private const TABLES_KEY = 'blog-tables-v4'; // v2: author column; v3: reactions and comments; v4: photo framing

    public const AUTHOR = 'JF Legaspi';

    public static function hasTables(): bool
    {
        return Schema::hasTable('blog_posts') && Schema::hasColumn('blog_posts', 'author') && Schema::hasColumn('media_assets', 'blog_post_id')
            && Schema::hasTable('blog_reactions') && Schema::hasTable('blog_comments') && Schema::hasColumn('media_assets', 'focus_y');
    }

    public static function ensureTables(): void
    {
        if (Cache::get(self::TABLES_KEY)) {
            return;
        }
        try {
            if (! Schema::hasTable('blog_posts')) {
                Schema::create('blog_posts', function (Blueprint $t) {
                    $t->uuid('id')->primary();
                    $t->string('slug', 80)->unique();
                    $t->string('title', 160);
                    $t->string('excerpt', 300)->default('');
                    $t->text('body');
                    $t->string('status', 10)->default('draft');
                    $t->timestamp('published_at')->nullable();
                    $t->boolean('featured')->default(false);
                    $t->integer('sort_order')->default(0);
                    $t->boolean('is_sample')->default(false);
                    $t->unsignedInteger('lock_version')->default(0);
                    $t->timestamps();
                    $t->index(['status', 'published_at']);
                });
            }
            if (! Schema::hasColumn('blog_posts', 'author')) {
                Schema::table('blog_posts', function (Blueprint $t) {
                    $t->string('author', 80)->nullable(); // blank = JF Legaspi
                });
            }
            if (! Schema::hasTable('blog_reactions')) {
                Schema::create('blog_reactions', function (Blueprint $t) {
                    $t->id();
                    $t->uuid('blog_post_id')->index();
                    $t->unsignedBigInteger('member_id');
                    $t->string('reaction', 20);
                    $t->timestamps();
                    $t->unique(['blog_post_id', 'member_id']);
                });
            }
            if (! Schema::hasTable('blog_comments')) {
                Schema::create('blog_comments', function (Blueprint $t) {
                    $t->id();
                    $t->uuid('blog_post_id')->index();
                    $t->unsignedBigInteger('member_id')->index();
                    $t->string('author_name', 100);
                    $t->text('body');
                    $t->string('status', 10)->default('review')->index(); // published | review | hidden
                    $t->string('check_note', 120)->nullable();             // why it was published or held
                    $t->timestamps();
                });
            }
            if (! Schema::hasColumn('media_assets', 'focus_y')) {
                Schema::table('media_assets', function (Blueprint $t) {
                    $t->unsignedTinyInteger('focus_y')->nullable(); // banner framing: where the faces are, % from the top
                });
            }
            if (! Schema::hasColumn('media_assets', 'blog_post_id')) {
                Schema::table('media_assets', function (Blueprint $t) {
                    $t->uuid('blog_post_id')->nullable()->index();
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

    /** Published posts, newest first, each with ->cover and ->photos. */
    public static function published(): Collection
    {
        self::ensureTables();
        $posts = BlogPost::query()->where('status', 'published')
            ->when(Site::isProduction(), fn ($q) => $q->where('is_sample', false))
            ->orderByDesc('published_at')->orderByDesc('created_at')->get();

        return self::withImages($posts);
    }

    public static function find(string $slug): ?BlogPost
    {
        return self::published()->firstWhere('slug', $slug);
    }

    private static function withImages(Collection $posts): Collection
    {
        $images = $posts->isEmpty() ? collect() : MediaAsset::query()->whereIn('blog_post_id', $posts->pluck('id'))->where('kind', 'image')
            ->orderBy('sort_order')->orderBy('created_at')->get()->groupBy('blog_post_id');
        foreach ($posts as $p) {
            $p->photos = $images->get($p->id, collect());
            $p->cover = $p->photos->firstWhere('is_cover', true) ?? $p->photos->first();
        }

        return $posts;
    }

    /** Name shown in the post's byline (JF Legaspi unless another author is set). */
    public static function author(BlogPost $p): string
    {
        return trim((string) $p->author) ?: self::AUTHOR;
    }

    /** Smallest photo width used for the blog page banner (smaller photos look blurry that large). */
    public const BANNER_MIN_WIDTH = 900;

    /** Choices for a photo's banner framing in the admin: % from the top where the faces are. */
    public const FOCUS_CHOICES = [0, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55, 60, 65, 70];

    /**
     * CSS object-position for a cropped blog photo (banners and list thumbnails), so faces stay in view.
     * Uses the framing set in the admin; otherwise tall photos keep the top and others sit a little above the middle.
     */
    public static function focus(\App\Models\MediaAsset $m): string
    {
        if ($m->focus_y !== null) {
            return '50% '.(int) $m->focus_y.'%';
        }

        return $m->height > $m->width * 1.15 ? '50% 3%' : '50% 30%';
    }

    /** Date shown on a post, e.g. "12 November 2026". */
    public static function date(BlogPost $p): string
    {
        return ($p->published_at ?? $p->created_at)->timezone('Asia/Manila')->format('j F Y');
    }
}
