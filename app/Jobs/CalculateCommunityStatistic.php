<?php

namespace App\Jobs;

use App\Consts;
use App\Events\CommunityInfoUpdated;
use App\Exceptions\Reports\InvalidActionException;
use App\Models\Community;
use App\Models\CommunityMember;
use App\Models\CommunityRequest;
use DB;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateCommunityStatistic implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $communityId;

    /**
     * Create a new job instance.
     *
     * @param $communityId
     */
    public function __construct($communityId)
    {
        $this->communityId = $communityId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        DB::beginTransaction();
        try {
            $this->process();
            DB::commit();
            return;
        } catch (Exception $exception) {
            DB::rollback();
            $this->log($exception);
        }
    }

    public function process()
    {
        $community = Community::where('id', $this->communityId)->first();
        if (!$community) {
            throw new InvalidActionException();
        }
        $totalRequest =  CommunityRequest::where(['community_id' => $this->communityId, 'status' => Consts::COMMUNITY_STATUS_CREATED])->count();
        $communityMember = CommunityMember::where('community_id', $this->communityId)->get();
        $countUsers = $this->calculateTotalUser($communityMember);
        $community->total_users = $countUsers['total_users'];
        $community->leader_count = $countUsers['leader_count'];
        $community->member_count = $countUsers['member_count'];
        $community->total_request = $totalRequest;
        $community->save();

        $countUsers['total_request'] = $totalRequest;

        $custom = collect($countUsers);
        $community = $custom->merge($community);

        event(new CommunityInfoUpdated($community));

        return $community;
    }

    private function calculateTotalUser($communityMember)
    {
        $totalLeaderCount = 0;
        $totalMemberCount = 0;

        $communityMember->each(function ($item) use (&$totalLeaderCount, &$totalMemberCount) {
            if (in_array($item->role, [Consts::COMMUNITY_ROLE_OWNER, Consts::COMMUNITY_ROLE_LEADER])) {
                $totalLeaderCount += 1;
            }
            if ($item->role === Consts::COMMUNITY_ROLE_MEMBER) {
                $totalMemberCount += 1;
            }
        });

        return [
            'leader_count' => $totalLeaderCount,
            'member_count' => $totalMemberCount,
            'total_users' => $totalLeaderCount + $totalMemberCount
        ];
    }

    private function log(...$params)
    {
        logger('==========CalculateCommunityStatistic: ', [$params]);
    }
}
