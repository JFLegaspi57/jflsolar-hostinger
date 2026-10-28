<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/** A staff account. Owners manage content and staff; editors manage content. */
class User extends Authenticatable
{
    protected $fillable = ['name', 'email', 'password', 'role', 'active', 'must_change_password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'active' => 'boolean',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }
}
