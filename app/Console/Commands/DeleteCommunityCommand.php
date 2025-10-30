<?php

namespace App\Console\Commands;

use App\Consts;
use App\Events\CommunityInfoUpdated;
use App\Models\Community;
use Carbon\Carbon;
use Illuminate\Console\Command;
use DB;

class DeleteCommunityCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete-community:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete community beyond the grace period';

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
        $ids = Community::where('inactive_at', '<=', Carbon::now()->subDays(Consts::COMMUNITY_DAYS_FOR_GRACE_PERIOD))->where('status', Consts::COMMUNITY_STATUS_DELETED)->pluck('id');
        if (count($ids) > 0) {
            DB::beginTransaction();
            try {
                logger()->info("==============[Deleted communities Ids]: ", [$ids]);
                Community::whereIn('id', $ids)->update(['deleted_at' => Carbon::now()]);
                foreach ($ids as $item) {
                    $data = Community::where('id', $item)->withTrashed()->first();
                    event(new CommunityInfoUpdated($data));
                }
                DB::commit();
            } catch (\Exception $ex) {
                DB::rollback();
                logger()->error($ex);
            }
        }
    }
}
