<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRanking extends Model
{

    protected $fillable = [
        'user_id',
        'ranking_id',
        'total_exp',
        'intro_step',
        'checkin_milestone'
    ];

    public function ranking()
    {
        return $this->hasOne('App\Models\Ranking', 'id', 'ranking_id');
    }
}
