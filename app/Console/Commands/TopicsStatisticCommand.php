<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\TopicsStatisticJob;
use App\Models\UserVideoTopic;

class TopicsStatisticCommand extends Command
{
        /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'topic-statistic:calculate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate Topic Statistic';

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
        $topics = UserVideoTopic::join('users', 'users.id', 'user_video_topics.user_id')
            ->select('users.username', 'user_video_topics.*')
            ->get();

        foreach ($topics as $value) {
            $user = (object) [
                'id'        => $value->user_id,
                'username'  => $value->username
            ];
            TopicsStatisticJob::dispatchNow($user, $value->topic_id);
        }

        return true;
    }
}
