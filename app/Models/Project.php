<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'system_size_kwp' => 'float',
            'system_size_confirmed' => 'boolean',
            'featured' => 'boolean',
            'is_sample' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function media(): HasMany
    {
        return $this->hasMany(MediaAsset::class)->orderBy('sort_order')->orderBy('created_at');
    }
}
