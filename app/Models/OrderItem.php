<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A line of an order, frozen at the price and name shown when the customer paid. */
class OrderItem extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'unit_price_php' => 'float',
            'line_total_php' => 'float',
            'quantity' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function unitLabel(): string
    {
        return $this->unit === 'm' ? ($this->quantity === 1 ? 'metre' : 'metres') : ($this->quantity === 1 ? 'piece' : 'pieces');
    }
}
