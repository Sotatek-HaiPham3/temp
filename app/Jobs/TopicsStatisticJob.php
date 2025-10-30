<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Utils\BigNumber;
use App\Models\TopicStatistic;
use Nodebb;

class TopicsStatisticJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $topicId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user, $topicId)
    {
        $this->user     = $user;
        $this->topicId  = $topicId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $topic = Nodebb::getPostsForTopic($this->user->username, $this->topicId);

        $statistic = TopicStatistic::firstOrCreate([
            'user_id' => $this->user->id,
            'topic_id' => $topic->tid
        ]);

        $statistic->total_comments = BigNumber::new($topic->postcount)->sub(1)->toString();
        $statistic->save();

        return true;
    }
}
