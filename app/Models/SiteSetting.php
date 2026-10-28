<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Public contact details and general warranty text. One row, id = 1. */
class SiteSetting extends Model
{
    public $incrementing = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'whatsapp_enabled' => 'boolean',
            'viber_enabled' => 'boolean',
            'facebook_enabled' => 'boolean',
            'messenger_enabled' => 'boolean',
        ];
    }
}
