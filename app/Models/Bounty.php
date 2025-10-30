<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BountyReview;
use App\Consts;

class Bounty extends Model
{
    use SoftDeletes;

    protected $table = 'bounties';

    protected $fillable = [
        'user_id',
        'game_id',
        'bounty_claim_request_id',
        'price',
        'escrow_balance',
        'fee',
        'title',
        'description',
        'gamelancer_type',
        'slug',
        'media',
        'status',
        'user_has_review',
        'claimer_has_review',
        'reason_id',
        // 'rank_id',
        // 'user_level_meta_id',
        'stopped_at'
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User')
            ->select('id', 'avatar', 'username', 'audio', 'sex', 'last_time_active', 'is_vip', 'user_type')
            ->with(['statistic']);
    }

    public function game()
    {
        return $this->belongsTo('App\Models\Game')
            ->select('id', 'title', 'slug', 'logo', 'thumbnail', 'portrait', 'is_active');
    }

    public function requests()
    {
        return $this->hasMany('App\Models\BountyClaimRequest');
    }

    public function activeRequests()
    {
        return $this->hasMany('App\Models\BountyClaimRequest')
            ->whereIn('status', [Consts::CLAIM_BOUNTY_REQUEST_STATUS_PENDING, Consts::CLAIM_BOUNTY_REQUEST_STATUS_APPROVED]);
    }

    public function pendingRequests()
    {
        return $this->hasMany('App\Models\BountyClaimRequest')
            ->where('status', Consts::CLAIM_BOUNTY_REQUEST_STATUS_PENDING);
    }

    public function claimBountyRequest()
    {
        return $this->hasOne('App\Models\BountyClaimRequest', 'id', 'bounty_claim_request_id')
            ->with(['claimerInfo']);
    }

    public function bountyPlatforms()
    {
        return $this->hasMany('App\Models\BountyPlatform')
            ->select('bounty_id', 'platform_id');
    }

    // public function bountyServers()
    // {
    //     return $this->hasMany('App\Models\BountyServer')
    //         ->select('bounty_id', 'game_server_id');
    // }

    public function userLevelMeta()
    {
        return $this->hasOne('App\Models\UserLevelMeta', 'id', 'user_level_meta_id');
    }

    public function rank()
    {
        return $this->hasOne('App\Models\GameRank', 'id', 'rank_id');
    }

    public function userReport()
    {
        return $this->hasOne('App\Models\UserReport', 'id', 'reason_id');
    }

    public function createOrUpdateBountyPlatfroms($platformIds)
    {
        if (collect($platformIds)->isEmpty()) {
            return;
        }
        $this->bountyPlatforms()->delete();

        $data = collect($platformIds)->map(function ($platformId) {
            return ['platform_id' => $platformId];
        });

        $this->bountyPlatforms()->createMany($data);

        return $this;
    }

    public function createOrUpdateBountyServers($serverIds)
    {
        if (collect($serverIds)->isEmpty()) {
            return;
        }
        $this->bountyServers()->delete();

        $data = collect($serverIds)->map(function ($serverId) {
            return ['game_server_id' => $serverId];
        });

        $this->bountyServers()->createMany($data);

        return $this;
    }
}
