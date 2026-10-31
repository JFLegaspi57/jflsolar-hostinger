<?php

namespace App\Models;

use App\Mail\MemberPasswordReset;
use App\Support\Site;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Mail;

/**
 * A customer / forum member account (sign up on the public site). Separate from staff
 * (App\Models\User): members never reach the admin.
 */
class Member extends Authenticatable
{
    protected $fillable = ['name', 'email', 'phone', 'password', 'status'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'forum_trusted' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'member_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** Password reset email: our own message and link (members have no named routes). */
    public function sendPasswordResetNotification($token): void
    {
        Mail::to($this->email)->send(new MemberPasswordReset($this, Site::url('/reset-password/'.$token.'?email='.rawurlencode($this->email))));
    }
}
