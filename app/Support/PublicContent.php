<?php

namespace App\Support;

use App\Models\ContentBlock;
use App\Models\MediaAsset;
use App\Models\Package;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Project;
use App\Models\SiteSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Everything the public site may show, with the visibility rules applied:
 * - packages/products/projects: published, and not samples on the live site;
 * - content blocks: published, and confirmed on the live site;
 * - categories: published; products only in a published category;
 * - packages whose promotion ended with "hide when expired" are hidden.
 *
 * Raw rows are cached (forever, cleared by every admin save via flush());
 * date-dependent rules (promotions) are applied per request.
 */
final class PublicContent
{
    // v2: adds page text edits. Changing the key makes existing installs rebuild the cache.
    private const CACHE_KEY = 'public-content:v2';

    private static ?array $memo = null;

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        self::$memo = null;
    }

    /** @return array{settings: SiteSetting, blocks: Collection, categories: Collection, packages: Collection, products: Collection, projects: Collection, blockImages: Collection, texts: array<string, string>} */
    public static function snapshot(): array
    {
        if (self::$memo !== null) {
            return self::$memo;
        }
        // The cache holds plain column arrays (the cache refuses to unserialize
        // objects); models are rebuilt from them on each request.
        $rows = Cache::rememberForever(self::CACHE_KEY, fn () => self::load());
        $raw = [
            'settings' => (new SiteSetting)->newFromBuilder($rows['settings']),
            'blocks' => ContentBlock::hydrate($rows['blocks']),
            'categories' => ProductCategory::hydrate($rows['categories']),
            'packages' => Package::hydrate($rows['packages']),
            'products' => Product::hydrate($rows['products']),
            'projects' => Project::hydrate($rows['projects']),
            'media' => MediaAsset::hydrate($rows['media']),
            'texts' => $rows['texts'],
        ];

        return self::$memo = self::applyRules($raw, Pricing::manilaToday());
    }

    private static function load(): array
    {
        $settings = SiteSetting::query()->find(1);
        if (! $settings) {
            throw new \RuntimeException('site_settings row is missing — import the starting content (database seeder or migration).');
        }
        $rows = fn ($query) => $query->get()->map(fn ($m) => $m->getAttributes())->all();

        return [
            'settings' => $settings->getAttributes(),
            'blocks' => $rows(ContentBlock::query()->orderBy('sort_order')),
            'categories' => $rows(ProductCategory::query()->orderBy('sort_order')),
            'packages' => $rows(Package::query()->orderBy('sort_order')->orderBy('created_at')),
            'products' => $rows(Product::query()->orderBy('sort_order')->orderBy('created_at')),
            'projects' => $rows(Project::query()->orderBy('sort_order')->orderBy('created_at')),
            'media' => $rows(MediaAsset::query()->orderBy('sort_order')->orderBy('created_at')),
            'texts' => PageText::overrides(),
        ];
    }

    private static function applyRules(array $raw, string $today): array
    {
        $production = Site::isProduction();
        $visible = fn ($r) => $r->status === 'published' && (! $production || ! $r->is_sample);
        $media = $raw['media'];

        $categories = $raw['categories']->where('status', 'published')->values();
        $categoryById = $categories->keyBy('id');

        $packages = $raw['packages']->filter($visible)->map(function (Package $p) use ($media, $today) {
            $p = clone $p;
            $p->promo_state = $p->promoState($today);
            $p->images = $media->where('package_id', $p->id)->where('kind', 'image')->values();

            return $p;
        })->reject(fn ($p) => $p->promo_state === 'ended' && $p->hide_when_expired)->values();

        $products = $raw['products']->filter($visible)->filter(fn ($p) => $categoryById->has($p->category_id))->map(function (Product $p) use ($media, $categoryById) {
            $p = clone $p;
            $own = $media->where('product_id', $p->id);
            $p->setRelation('category', $categoryById->get($p->category_id));
            $p->images = $own->where('kind', 'image')->values();
            $p->datasheet = $own->firstWhere('kind', 'datasheet');

            return $p;
        })->values();

        $projects = $raw['projects']->filter($visible)->map(function (Project $p) use ($media) {
            $p = clone $p;
            $p->photos = $media->where('project_id', $p->id)->where('kind', 'image')->values();
            $p->cover = $p->photos->firstWhere('is_cover', true) ?? $p->photos->first();

            return $p;
        })->values();

        $blocks = $raw['blocks']->filter(fn ($b) => $b->published && (! $production || $b->claim_status === 'confirmed'))->values();
        $visibleKeys = $blocks->pluck('key')->flip();
        $blockImages = $media->where('kind', 'image')->filter(fn ($m) => $m->content_block_key && $visibleKeys->has($m->content_block_key))->groupBy('content_block_key');

        return [
            'settings' => $raw['settings'],
            'blocks' => $blocks,
            'categories' => $categories,
            'packages' => $packages,
            'products' => $products,
            'projects' => $projects,
            'blockImages' => $blockImages,
            'texts' => $raw['texts'],
        ];
    }

    public static function settings(): SiteSetting
    {
        return self::snapshot()['settings'];
    }

    public static function blocks(string $section): Collection
    {
        return self::snapshot()['blocks']->where('section', $section)->values();
    }

    public static function block(string $key): ?ContentBlock
    {
        return self::snapshot()['blocks']->firstWhere('key', $key);
    }

    public static function package(string $slug): ?Package
    {
        return self::snapshot()['packages']->firstWhere('slug', $slug);
    }

    public static function product(string $slug): ?Product
    {
        return self::snapshot()['products']->firstWhere('slug', $slug);
    }

    public static function project(string $slug): ?Project
    {
        return self::snapshot()['projects']->firstWhere('slug', $slug);
    }
}
