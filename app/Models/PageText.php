<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One staff edit to the page wording; see App\Support\PageText. */
class PageText extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CREATED_AT = null;

    protected $guarded = [];
}
