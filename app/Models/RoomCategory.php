<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoomCategory extends Model
{
    use SoftDeletes;

    protected $table = 'room_categories';

    protected $fillable = [
        'game_id',
        'type',
        'size_range',
        'total_user',
        'total_room',
        'label',
        'image',
        'description',
        'is_public',
        'pinned'
    ];

    public function game()
    {
        return $this->hasOne('App\Models\Game', 'id', 'game_id')
            ->select('id', 'title', 'logo', 'cover', 'slug', 'ios_app_id', 'android_app_id');
    }
}
