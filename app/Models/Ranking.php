<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ranking extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'exp',
        'threshold_exp_in_day',
        'url',
        'order'
    ];
}
