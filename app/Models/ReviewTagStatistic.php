<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewTagStatistic extends Model
{
    protected $fillable = [
        'user_id',
        'review_tag_id',
        'quantity',
        'review_type',
        'executed_date'
    ];
}
