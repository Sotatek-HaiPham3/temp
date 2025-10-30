<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Services\Mattermost;

class BountyClaimRequest extends Model
{
    protected $fillable = [
        'bounty_id',
        'gamelancer_id',
        'channel_id',
        'reason_id',
        'description',
        'status'
    ];

    public function bounty()
    {
        return $this->belongsTo('App\Models\Bounty')
            ->with(['user', 'game']);
    }

    public function claimerInfo()
    {
        return $this->hasOne('App\Models\User', 'id', 'gamelancer_id')
            ->select('id', 'sex', 'avatar', 'username');
    }

    public function channel()
    {
        return $this->hasOne('App\Models\Channel', 'id', 'channel_id')
            ->select('id', 'mattermost_channel_id');
    }
}
