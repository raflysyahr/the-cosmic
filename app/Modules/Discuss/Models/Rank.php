<?php

namespace App\Modules\Discuss\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Rank extends Model
{
    use HasUlids, HasFactory;

    protected $table = 'discuss_ranks';

    protected $fillable = [
        'room_id', 'name', 'label_color', 'icon_url',
        'min_xp', 'order', 'perks',
    ];

    protected function casts(): array
    {
        return [
            'min_xp' => 'integer',
            'order' => 'integer',
            'perks' => 'array',
        ];
    }

    public $timestamps = false;

    public function scopeForRoom($query, ?string $roomId)
    {
        return $query->where(function ($q) use ($roomId) {
            $q->whereNull('room_id')->orWhere('room_id', $roomId);
        });
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('room_id');
    }
}
