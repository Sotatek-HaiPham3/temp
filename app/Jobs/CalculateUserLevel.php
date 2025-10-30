<?php

namespace App\Jobs;

use App\Consts;
use App\Utils;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Models\User;
use App\Models\UserBounty;
use App\Events\UserUpdated;

class CalculateUserLevel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $userId;

    /**
     * Create a new job instance.
     *
     * @param $userId
     * @param $currencies
     */
    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $user = User::find($this->userId);
        if (!$user) {
            return false;
        }

        logger()->info("CalculateUserLevel::Current user's level is {$user->level}");

        switch ($user->level) {
            case 0:
                if (self::isValidConditionLevel1($user) ) {
                    self::updateUserLevel($user, 1);
                }
                break;
            case 1:
                if (self::isValidConditionLevel2()) {
                    self::updateUserLevel($user, 2);
                }
                break;
            default:
                break;
        }
    }

    private function isValidConditionLevel1(User $user)
    {
        return !empty($user->full_name) && !empty($user->description) && !empty($user->avatar);
    }

    private function isValidConditionLevel2() {
        return UserBounty::where(function ($query) {
                $query->where('claimer_id', $this->userId)
                    ->orWhere('user_id', $this->userId);
            })
            ->whereIn('status', [
                Consts::USER_BOUNTY_STATUS_STOPPED,
                Consts::USER_BOUNTY_STATUS_DISPUTED,
                Consts::USER_BOUNTY_STATUS_COMPLETED
            ])
            ->exists();
    }

    private function updateUserLevel(User $user, $newLevel)
    {
        $user->level = $newLevel;
        $user->save();

        event(new UserUpdated($user->id));

        return $user;
    }
}
