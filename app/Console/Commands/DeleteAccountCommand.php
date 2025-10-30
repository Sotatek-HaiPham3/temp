<?php

namespace App\Console\Commands;

use App\Consts;
use App\Models\Community;
use App\Models\CommunityMember;
use App\Models\SocialUser;
use App\Models\SystemNotification;
use App\Models\User;
use App\Models\UserFollowing;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteAccountCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete-account:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete account beyond the grace period';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $ids = User::where('deleted_at', '<=', Carbon::now()->subDays(Consts::USER_DAYS_FOR_GRACE_PERIOD))->where('status', Consts::USER_DELETED)->pluck('id');
        // Update username, email, phone and remove social login
        DB::beginTransaction();
        try {
            foreach ($ids as $id) {
                logger()->info("==============[Deleted User Ids]: ", [$ids]);

                User::where('id', $id)->update([
                    'username' => Consts::USER_DELETED_USERNAME_PREFIX . $id,
                    'email' => Consts::USER_DELETED_USERNAME_PREFIX . $id . '@gamelancer.com',
                    'email_verified' => 0,
                    'email_verification_code' => null,
                    'email_verification_code_created_at' => null,
                    'phone_number' => null,
                    'phone_country_code' => null,
                    'phone_verify_created_at' => null,
                    'phone_verify_code' => null,
                    'phone_verified' => 0,
                ]);

                // remove social user
                SocialUser::where('user_id', $id)->delete();

                // remove user_following
                UserFollowing::where('user_id', $id)->orWhere('following_id', $id)->delete();

                // remove community
                Community::where('creator_id', $id)->delete();

                // remove community member
                CommunityMember::where('user_id', $id)->delete();
            }

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            logger()->error($ex);
        }
    }
}
