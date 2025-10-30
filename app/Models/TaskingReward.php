<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskingReward extends Model
{
    protected $fillable = [
        'type',
        'level',
        'quantity',
        'currency'
    ];

    public function toData($collected = [])
    {
        return [
            'id'            => $this->id,
            'type'          => $this->type,
            'level'         => $this->level,
            'quantity'      => $this->quantity,
            'currency'      => $this->currency,
            'collected'     => !empty($collected[$this->id])
        ];
    }
}
