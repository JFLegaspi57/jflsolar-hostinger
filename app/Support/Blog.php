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
    private const TABLES_KEY = 'blog-tables-v1';

    public const AUTHOR = 'JF Legaspi';

    public static function hasTables(): bool
    {
        return Schema::hasTable('blog_posts') && Schema::hasColumn('media_assets', 'blog_post_id');
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

    /** Date shown on a post, e.g. "12 November 2026". */
    public static function date(BlogPost $p): string
    {
        return ($p->published_at ?? $p->created_at)->timezone('Asia/Manila')->format('j F Y');
    }
}
