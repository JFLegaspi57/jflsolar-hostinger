<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Editable copy (About, mission, vision, founder, why-choose-us …). */
class ContentBlock extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['published' => 'boolean', 'sort_order' => 'integer'];
    }

    public function media(): HasMany
    {
        return $this->hasMany(MediaAsset::class, 'content_block_key', 'key')->orderBy('sort_order')->orderBy('created_at');
    }

    public function isConfirmed(): bool
    {
        return $this->claim_status === 'confirmed';
    }
}
