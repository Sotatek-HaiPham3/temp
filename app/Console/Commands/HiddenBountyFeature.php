<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Utils;
use App\Consts;
use App\Models\Bounty;
use App\Models\BountyClaimRequest;
use Exception;
use DB;
use App\Http\Services\UserService;

class HiddenBountyFeature extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bounty:close';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Closing All Bounties';

    private $userService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->userService = new UserService();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (Utils::isProduction() && !$this->confirm('Do you wish to continue?')) {
            return;
        }

        DB::beginTransaction();
        try {
            $this->cancelBountyClaimingRequests();
            $this->closeBounties();
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            logger()->error($ex);
        }
    }

    private function cancelBountyClaimingRequests()
    {
        $statusList = [
            Consts::CLAIM_BOUNTY_REQUEST_STATUS_PENDING
        ];
        $requests = BountyClaimRequest::where('status', $statusList)->get();

        return $requests->map(function ($record) {
            $record->status = Consts::CLAIM_BOUNTY_REQUEST_STATUS_CANCELED;
            $record->save();

            return $record->id;
        })->values();
    }

    private function closeBounties()
    {
        $statusList = [
            Consts::BOUNTY_STATUS_CREATED
        ];
        $bounties = Bounty::where('status', $statusList)->get();
        $bounties->each(function ($bounty) {
            $bounty->delete();

            $this->userService->addMoreBalance($bounty->user_id, $bounty->escrow_balance);
        });
    }

}
