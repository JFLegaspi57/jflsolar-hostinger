<?php

namespace App\Models;

use App\Support\Shop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** An online order. Created before payment; PayMongo's webhook marks it paid. */
class Order extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['access_token'];

    protected function casts(): array
    {
        return [
            'total_php' => 'float',
            'amount_paid_php' => 'float',
            'paid_at' => 'datetime',
            'emailed_at' => 'datetime',
            'lock_version' => 'integer',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('id');
    }

    /** Printable order summary (customer's copy). */
    public function printPath(): string
    {
        return '/orders/'.$this->reference.'/print?t='.$this->access_token;
    }

    public function statusLabel(): string
    {
        return Shop::STATUSES[$this->status] ?? $this->status;
    }

    /** Private link the customer uses to see their order (unguessable token, no account needed). */
    public function publicPath(): string
    {
        return '/orders/'.$this->reference.'?t='.$this->access_token;
    }
}
