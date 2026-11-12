<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A post on JF Legaspi's blog (/blog). Table created on first use: App\Support\Blog::ensureTables(). */
class BlogPost extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'featured' => 'boolean',
            'is_sample' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // The date shown on a post is when it was first published.
        static::saving(function (BlogPost $p) {
            if ($p->status === 'published' && ! $p->published_at) {
                $p->published_at = now();
            }
        });
    }

    public function media(): HasMany
    {
        return $this->hasMany(MediaAsset::class, 'blog_post_id')->orderBy('sort_order')->orderBy('created_at');
    }
}
