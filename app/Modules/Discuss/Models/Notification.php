<?php

namespace App\Modules\Discuss\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasUlids, HasFactory;

    protected $table = 'discuss_notifications';

    protected $fillable = [
        'user_id', 'type', 'actor_user_id', 'payload', 'is_read',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'is_read' => 'boolean',
        ];
    }

    public $timestamps = false;

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
}
