<?php

namespace App\Support;

use App\Models\MediaAsset;

/**
 * Media files live outside the web root in storage/app/media/<storage_key>/<file>,
 * where storage_key = <folder>/<owner uuid>/<asset uuid> and file is <width>.webp
 * or datasheet.pdf. Public access is decided per request from the owning item.
 */
final class MediaStore
{
    public const FOLDERS = ['packages', 'products', 'projects', 'blocks'];

    private const UUID = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

    public static function root(): string
    {
        return storage_path('app/media');
    }

    public static function path(string $storageKey, string $file = ''): string
    {
        return self::root().'/'.$storageKey.($file !== '' ? '/'.$file : '');
    }

    /** Validates a requested path; returns [storage_key, file, content type] or null. */
    public static function parse(string $path): ?array
    {
        // Widths: the standard 640/1280/2000, or a smaller original's own width;
        // the controller also requires the file to be listed on the asset.
        $re = '#^((?:'.implode('|', self::FOLDERS).')/'.self::UUID.'/'.self::UUID.')/([1-9][0-9]{0,3}\.webp|datasheet\.pdf|manual\.pdf)$#';
        if (! preg_match($re, $path, $m)) {
            return null;
        }

        return [$m[1], $m[2], str_ends_with($m[2], '.pdf') ? 'application/pdf' : 'image/webp'];
    }

    /** Whether an asset may be shown to the public right now (same rules as PublicContent). */
    public static function isPublic(MediaAsset $m): bool
    {
        $production = Site::isProduction();
        $record = fn ($r) => $r && $r->status === 'published' && (! $production || ! $r->is_sample);

        return match (true) {
            $m->package_id !== null => (function () use ($m, $record) {
                $p = \App\Models\Package::query()->find($m->package_id);

                // Same rule as the public listing: an ended promotion set to hide takes its images with it.
                return $record($p) && ! ($p->hide_when_expired && $p->promoState() === 'ended');
            })(),
            $m->product_id !== null => (function () use ($m, $record) {
                $p = \App\Models\Product::query()->with('category')->find($m->product_id);

                return $record($p) && $p->category?->status === 'published';
            })(),
            $m->project_id !== null => $record(\App\Models\Project::query()->find($m->project_id)),
            $m->content_block_key !== null => (function () use ($m, $production) {
                $b = \App\Models\ContentBlock::query()->find($m->content_block_key);

                return $b && $b->published && (! $production || $b->claim_status === 'confirmed');
            })(),
            default => false,
        };
    }

    /** Delete all stored files of an asset (and its now-empty folder). */
    public static function deleteFiles(MediaAsset $m): void
    {
        $dir = self::path($m->storage_key);
        foreach ($m->files() as $file) {
            @unlink($dir.'/'.$file);
        }
        @rmdir($dir);
        @rmdir(dirname($dir)); // owner folder, only if empty
    }
}
