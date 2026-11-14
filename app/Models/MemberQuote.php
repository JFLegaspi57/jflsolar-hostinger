<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A member's "Make a Quotation" list: products and their own items, sent to JFL Solar as a quote request. */
class MemberQuote extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['items' => 'array', 'sent_at' => 'datetime', 'edited_after_send' => 'boolean'];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /** Short reference shown to the member and staff, e.g. Q-00012. */
    public function reference(): string
    {
        return 'Q-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }
}
