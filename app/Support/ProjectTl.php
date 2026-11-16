<?php

namespace App\Support;

use App\Models\Project;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Filipino (Tagalog) version of a project page, shown at /projects/{slug}/tagalog with the
 * same photos and videos as the English page. Written in Admin → Projects. On Hostinger the
 * columns are added on first use (no migrations there).
 */
final class ProjectTl
{
    private const KEY = 'project-tl-columns-v1';

    public const TYPE = ['residential' => 'Residensyal', 'commercial' => 'Komersyal', 'industrial' => 'Industriyal'];

    public static function hasColumns(): bool
    {
        return Schema::hasColumn('projects', 'description_tl');
    }

    public static function ensureColumns(): void
    {
        if (Cache::get(self::KEY)) {
            return;
        }
        try {
            if (! self::hasColumns()) {
                Schema::table('projects', function (Blueprint $t) {
                    $t->string('title_tl', 160)->nullable();
                    $t->string('summary_tl', 300)->nullable();
                    $t->text('description_tl')->nullable();
                });
                PublicContent::flush();
            }
        } catch (\Throwable $e) {
            if (! self::hasColumns()) {
                throw $e;
            }
        }
        Cache::forever(self::KEY, true);
    }

    /** ['title', 'summary', 'description'] in Filipino, or null when the project has no Filipino version. */
    public static function tagalog(Project $p): ?array
    {
        $description = trim((string) ($p->description_tl ?? ''));
        if ($description === '') {
            return null;
        }

        return [
            'title' => trim((string) ($p->title_tl ?? '')) ?: $p->title,
            'summary' => trim((string) ($p->summary_tl ?? '')) ?: (string) $p->summary,
            'description' => $description,
        ];
    }
}
