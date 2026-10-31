<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A forum question / discussion. Its opening message is the first ForumPost. */
class ForumTopic extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['pinned' => 'boolean', 'locked' => 'boolean', 'last_post_at' => 'datetime', 'replies_count' => 'integer'];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(ForumPost::class, 'topic_id');
    }

    public function path(): string
    {
        return '/forum/'.$this->slug;
    }
}
