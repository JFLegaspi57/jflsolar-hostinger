<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One member's reaction to a blog post (one per member per post; can be changed or removed). */
class BlogReaction extends Model
{
    protected $guarded = ['id'];
}
