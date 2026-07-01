<?php

namespace App\Modules\Auth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class UserSocialAccount extends Model
{
    use HasUlids, HasFactory;

    protected $fillable = [
        'user_id', 'provider', 'provider_id',
        'access_token', 'refresh_token', 'token_expires_at',
    ];

    public $timestamps = false;

    protected $createdAt = 'created_at';
}
