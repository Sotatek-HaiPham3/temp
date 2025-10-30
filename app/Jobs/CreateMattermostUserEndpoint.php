<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\MattermostUser;
use App\Utils;
use DB;
use Mattermost;

class CreateMattermostUserEndpoint implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $userId;
    private $email;
    private $username;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user)
    {
        $this->userId = $user->id;
        $this->email = $user->email;
        $this->username = $user->username;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $email = Utils::generateAutoEmail();
        $username = Utils::generateAutoUsername($this->username);
        $mattermostUser = Mattermost::createUserEndpoint($email, $username);

        MattermostUser::create([
            'user_id' => $this->userId,
            'mattermost_user_id' => $mattermostUser->id,
            'mattermost_email' => $email
        ]);
    }
}
