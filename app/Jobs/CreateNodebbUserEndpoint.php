<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\NodebbUser;
use Nodebb;
use App\Events\UserProfileUpdated;

class CreateNodebbUserEndpoint implements ShouldQueue
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
        $nodebbUser = Nodebb::createUserEndpoint($this->email, $this->username);

        NodebbUser::create([
            'user_id' => $this->userId,
            'nodebb_user_id' => $nodebbUser->payload->uid,
        ]);

        event(new UserProfileUpdated($this->userId));
    }
}
