<?php

namespace App\Modules\Discuss\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Reaction extends Model
{
    use HasUlids, HasFactory;

    protected $table = 'discuss_reactions';

    protected $fillable = [
        'message_id', 'user_id', 'emote_id',
    ];

    public $timestamps = false;
}
