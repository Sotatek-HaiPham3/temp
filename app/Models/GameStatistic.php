<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Consts;

class GameStatistic extends Model
{
    protected $table = 'game_statistics';

    protected $fillable = [
        'game_id',
        'user_ids',
        'total_sessions',
        'total_coins',
        'total_bars',
        'total_quantity_per_game',
        'total_quantity_per_hour',
        'total_videos',
        'executed_date'
    ];

    public function setUserIdsAttribute($value)
    {
        $this->attributes['user_ids'] = $value ? implode(Consts::CHAR_COMMA, $value) : null;
    }

    public function getUserIdsAttribute($value)
    {
        if (empty($value)) {
            return [];
        }

        return explode(Consts::CHAR_COMMA, $value);
    }
}
