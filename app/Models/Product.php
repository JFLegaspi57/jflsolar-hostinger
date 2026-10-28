<?php

namespace App\Models;

use App\Support\Pricing;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'specs' => 'array',
            'price_php' => 'float',
            'price_is_sample' => 'boolean',
            'featured' => 'boolean',
            'is_sample' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(MediaAsset::class)->orderBy('sort_order')->orderBy('created_at');
    }

    public function price(): array
    {
        return Pricing::display($this->pricing_mode, $this->price_php, $this->price_basis, $this->price_is_sample);
    }
}
