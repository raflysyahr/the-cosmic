<?php

namespace App\Modules\Discuss\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Emote extends Model
{
    use HasUlids, HasFactory;

    protected $table = 'discuss_emotes';

    protected $fillable = [
        'room_id', 'code', 'name', 'image_url',
        'is_animated', 'is_active', 'uploaded_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_animated' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public $timestamps = false;

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('room_id');
    }

    public function scopeForRoom($query, string $roomId)
    {
        return $query->where(function ($q) use ($roomId) {
            $q->whereNull('room_id')->orWhere('room_id', $roomId);
        });
    }
}
