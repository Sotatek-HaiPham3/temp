<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TopicStatistic extends Model
{
    protected $fillable = [
        'user_id',
        'topic_id',
        'total_comments'
    ];
}
