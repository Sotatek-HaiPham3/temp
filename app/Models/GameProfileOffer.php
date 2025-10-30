<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GameProfileOffer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'game_profile_id',
        'type',
        'quantity',
        'price'
    ];
}
