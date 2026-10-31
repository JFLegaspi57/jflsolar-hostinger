<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One message in a forum topic (the first one opens the topic). */
class ForumPost extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_opening' => 'boolean'];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(ForumTopic::class, 'topic_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function isStaff(): bool
    {
        return $this->staff_user_id !== null;
    }
}
