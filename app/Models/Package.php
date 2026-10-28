<?php

namespace App\Models;

use App\Support\Pricing;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'components' => 'array',
            'inclusions' => 'array',
            'exclusions' => 'array',
            'inverter_kw' => 'float',
            'array_kwp' => 'float',
            'battery_kwh' => 'float',
            'price_php' => 'float',
            'price_is_sample' => 'boolean',
            'promo_start' => 'date:Y-m-d',
            'promo_end' => 'date:Y-m-d',
            'hide_when_expired' => 'boolean',
            'featured' => 'boolean',
            'is_sample' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function media(): HasMany
    {
        return $this->hasMany(MediaAsset::class)->orderBy('sort_order')->orderBy('created_at');
    }

    public function promoStart(): ?string
    {
        return $this->promo_start?->format('Y-m-d');
    }

    public function promoEnd(): ?string
    {
        return $this->promo_end?->format('Y-m-d');
    }

    public function promoState(?string $today = null): string
    {
        return Pricing::promoState($this->promoStart(), $this->promoEnd(), $today);
    }

    /** Public price display, after promotion rules. */
    public function price(?string $promo = null): array
    {
        return Pricing::display($this->pricing_mode, $this->price_php, $this->price_basis, $this->price_is_sample, $promo ?? $this->promoState());
    }
}
