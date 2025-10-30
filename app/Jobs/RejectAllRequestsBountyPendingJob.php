<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Models\Bounty;
use App\Models\BountyClaimRequest;
use App\Http\Services\BountyService;
use App\Consts;
use Exception;

class RejectAllRequestsBountyPendingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $bounty;
    protected $bountyService;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($bounty)
    {
        $this->bounty = $bounty;
        $this->bountyService = new BountyService();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $pendingRequests = BountyClaimRequest::where('bounty_id', $this->bounty->id)
            ->where('status', Consts::CLAIM_BOUNTY_REQUEST_STATUS_PENDING)
            ->pluck('id');

        foreach ($pendingRequests as $value) {
            $this->bountyService->reject($value, null, Consts::REASON_CONTENT_BOUNTY_COMPLETED);
        }
    }
}
