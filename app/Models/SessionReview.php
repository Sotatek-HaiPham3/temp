<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SessionReview extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'object_id',
        'game_profile_id',
        'object_type',
        'reviewer_id',
        'user_id',
        'rate',
        'description',
        'recommend',
        'submit_at'
    ];

    public function userReview()
    {
        return $this->hasOne('App\Models\User', 'id', 'reviewer_id')
            ->select('id', 'username', 'avatar', 'sex');
    }

    public function tags()
    {
        return $this->hasMany('App\Models\SessionReviewTag', 'review_id', 'id')
            ->select('review_id', 'review_tag_id')
            ->with(['tagName']);
    }
}
