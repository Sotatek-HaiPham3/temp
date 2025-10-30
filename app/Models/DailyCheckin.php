<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyCheckin extends Model
{
    protected $fillable = [
        'user_id',
        'milestone',
        'day',
        'exp',
        'checked_at'
    ];

    public function toData()
    {
        return [
            'id'            => $this->id,
            'milestone'     => $this->milestone,
            'day'           => $this->day,
            'exp'           => $this->exp,
            'checked'       => !empty($this->checked_at)
        ];
    }
}
