<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionReviewTag extends Model
{
    protected $fillable = [
        'review_id',
        'review_tag_id'
    ];

    public function review()
    {
        return $this->hasOne('App\Models\SessionReview', 'id', 'review_id')
            ->select('id', 'object_type', 'user_id');
    }

    public function tagName()
    {
        return $this->hasOne('App\Models\ReviewTag', 'id', 'review_tag_id')
            ->select('id', 'content');
    }
}
