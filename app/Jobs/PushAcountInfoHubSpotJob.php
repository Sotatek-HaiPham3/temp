<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\User;
use HubSpot;
use App\Utils;

class PushAcountInfoHubSpotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $user;

    /**
     * Create a new job instance.
     *
     * @param $user
     * @param $currencies
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (!Utils::isProduction()) {
            return;
        }

        $properties = [
            ['property' => 'gender', 'value' => $this->user->sex ? 'Female' : 'Male'],
            ['property' => 'date_of_birth', 'value' => $this->user->dob],
        ];
        HubSpot::contacts()->createOrUpdate($this->user->email, $properties);
    }

}
