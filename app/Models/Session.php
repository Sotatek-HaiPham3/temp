<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\SessionReview;
use Carbon\Carbon;
use App\Consts;
use App\Utils;

class Session extends Model
{
    protected $fillable = [
        'gamelancer_id',
        'claimer_id',
        'game_profile_id',
        'offer_id',
        'type',
        'channel_id',
        'booked_at',
        'quantity',
        'quantity_played',
        'schedule_at',
        'start_at',
        'ready_at',
        'end_at',
        'escrow_balance',
        'fee',
        'reason_id',
        'claimer_stop',
        'gamelancer_stop',
        'claimer_ready',
        'next_game_user_id',
        'gamelancer_ready',
        'user_has_review',
        'claimer_has_review',
        'has_restart',
        'claimer_absent',
        'gamelancer_absent',
        'status'
    ];

    protected $appends = ['userCanCancel'];

    public function getUserCanCancelAttribute()
    {
        if ($this->booked_at !== $this->schedule_at) {
            return true;
        }

        $now = Carbon::now()->timestamp;
        $canCancelTime = Utils::millisecondsToCarbon($this->schedule_at)
            ->addSeconds(Consts::GAMEPROFILE_BOOK_NOW_USER_CAN_CANCEL)
            ->timestamp;

        return $now > $canCancelTime && $this->booked_at === $this->schedule_at;
    }

    public function addingRequests()
    {
        return $this->hasMany('App\Models\SessionAddingRequest');
    }

    public function pendingRequests()
    {
        return $this->hasOne('App\Models\SessionAddingRequest', 'session_id', 'id')
            ->where('status', Consts::SESSION_ADDING_REQUEST_STATUS_PENDING);
    }

    public function gameProfile()
    {
        return $this->belongsTo('App\Models\GameProfile')
            ->select('id', 'game_id', 'title')
            ->with(['game']);
    }

    public function gamelancerInfo()
    {
        return $this->hasOne('App\Models\User', 'id', 'gamelancer_id')
            ->select('id', 'sex', 'avatar', 'username');
    }

    public function claimerInfo()
    {
        return $this->hasOne('App\Models\User', 'id', 'claimer_id')
            ->select('id', 'sex', 'avatar', 'username');
    }

    public function channel()
    {
        return $this->hasOne('App\Models\Channel', 'id', 'channel_id')
            ->select('id', 'mattermost_channel_id');
    }

    public function gameOffer()
    {
        return $this->hasOne('App\Models\GameProfileOffer', 'id', 'offer_id')
            ->withTrashed();
    }

    public function reason()
    {
        return $this->hasOne('App\Models\Reason', 'id', 'reason_id');
    }

    public function rejectCompleteReason()
    {
        return $this->hasMany('App\Models\SessionReason', 'session_id', 'id')
            ->withTrashed();
    }

    public function sessionNotCompleted()
    {
        if ($this->status !== Consts::SESSION_STATUS_COMPLETED && $this->status !== Consts::SESSION_STATUS_STOPPED) {
            return true;
        }
        return false;
    }
}
