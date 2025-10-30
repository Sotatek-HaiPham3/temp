<?php

use Illuminate\Database\Seeder;

use Illuminate\Support\Str;
use App\Models\Ranking;
use App\Models\ExperiencePoint;
use App\Models\Tasking;
use App\Models\TaskingReward;
use App\Consts;

class LevelingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('rankings')->truncate();
        DB::table('experience_points')->truncate();
        DB::table('taskings')->truncate();
        DB::table('tasking_rewards')->truncate();

        $this->createRankings();
        $this->createExperiencePoints();
        $this->createTaskings();
        $this->createTaskingRewards();
    }

    private function createRankings()
    {
        $data = [
            ['order' => 1, 'name' => 'Iron', 'exp' => 0, 'threshold_exp_in_day' => 1000, 'url' => 'https://assets.gamelancer.com/data/badge/iron.svg'],
            ['order' => 2, 'name' => 'Bronze', 'exp' => 1000, 'threshold_exp_in_day' => 1000, 'url' => 'https://assets.gamelancer.com/data/badge/bronze.svg'],
            ['order' => 3, 'name' => 'Silver', 'exp' => 5000, 'threshold_exp_in_day' => 1000, 'url' => 'https://assets.gamelancer.com/data/badge/silver.svg'],
            ['order' => 4, 'name' => 'Gold', 'exp' => 15000, 'threshold_exp_in_day' => 1000, 'url' => 'https://assets.gamelancer.com/data/badge/gold.svg'],
            ['order' => 5, 'name' => 'Platinum', 'exp' => 60000, 'threshold_exp_in_day' => 1000, 'url' => 'https://assets.gamelancer.com/data/badge/platinum.svg'],
            ['order' => 6, 'name' => 'Diamond', 'exp' => 300000, 'threshold_exp_in_day' => 1000, 'url' => 'https://assets.gamelancer.com/data/badge/diamond.svg'],
        ];

        foreach ($data as $value) {
            Ranking::create(
                array_merge($value, [
                    'code' => Str::slug($value['name'])
                ])
            );
        }
    }

    private function createExperiencePoints()
    {
        $defaultExp = 10;

        for ($i = 1; $i < 15; $i++) {
            $exp = $defaultExp + ($i - 1) * 10;
            ExperiencePoint::create([
                'day' => $i,
                'exp' => $exp
            ]);
        }
    }

    private function createTaskings()
    {
        $introTasks = $this->getIntroTasks();
        $dailyTasks = $this->getDailyTasks();

        collect($introTasks)->concat($dailyTasks)->each(function ($value) {
            Tasking::create($value);
        });
    }

    private function getIntroTasks()
    {
        $type = Consts::TASKING_TYPE_INTRO;
        return [
            [
                'order'                     => 1,
                'type'                      => $type,
                'title'                     => 'Explore the platform',
                'code'                      => Tasking::EXPLORE_PLATFORM,
                'description'               => "It's time to explore, let us show you the main features that will allow you to connect with others and have more fun gaming!",
                'exp'                       => 200,
                'threshold_exp_in_day'      => 200
            ],
            [
                'order'                     => 2,
                'type'                      => $type,
                'title'                     => 'Create a Session',
                'code'                      => Tasking::CREATE_SESSION,
                'description'               => 'Creating a session will enable other gamers to request to play with you. Get started, connect with other likeminded gamers and have fun!',
                'exp'                       => 200,
                'threshold_exp_in_day'      => 200
            ],
            [
                'order'                     => 3,
                'type'                      => $type,
                'title'                     => 'Upload a Video',
                'code'                      => Tasking::UPLOAD_VIDEO_INTRO,
                'description'               => "Upload your first video, and show the gaming world what you're all about! BTW your video might be featured to our 250M+ monthly viewers, exciting right?",
                'exp'                       => 200,
                'threshold_exp_in_day'      => 200
            ],
            [
                'order'                     => 4,
                'type'                      => $type,
                'title'                     => 'Play a Session',
                'code'                      => Tasking::PLAY_FREE_SESSION,
                'description'               => 'Complete a play session by either booking another Gamelancer or having someone book you! Your play session is shareable so let your friends know by sharing the link on your social media accounts!',
                'exp'                       => 200,
                'threshold_exp_in_day'      => 200
            ]
        ];
    }

    private function getDailyTasks()
    {
        $type = Consts::TASKING_TYPE_DAILY;
        return [
            [
                'order'                     => 1,
                'type'                      => $type,
                'title'                     => "Follow",
                'code'                      => Tasking::FOLLOW_USER,
                'description'               => "Follow some users on the platform that you'd like to play with or to get updated with their new content",
                'exp'                       => 50,
                'threshold_exp_in_day'      => 100
            ],
            [
                'order'                     => 2,
                'type'                      => $type,
                'title'                     => "Upload a Video",
                'code'                      => Tasking::UPLOAD_VIDEO_DAILY,
                'description'               => "Upload videos daily for a better chance to be featured and show your skills / personality",
                'exp'                       => 50,
                'threshold_exp_in_day'      => 100
            ],
            [
                'order'                     => 3,
                'type'                      => $type,
                'title'                     => "Complete a Session (excludes free sessions)",
                'code'                      => Tasking::COMPLETE_SESSION,
                'description'               => "Connect and play sessions with other Gamelancers to gain experience quicker",
                'exp'                       => 100,
                'threshold_exp_in_day'      => 200
            ]
        ];
    }

    private function createTaskingRewards()
    {
        $data = [
            ['type' => Consts::TASKING_TYPE_INTRO, 'level' => 1, 'quantity' => 50, 'currency' => Consts::CURRENCY_EXP],
            ['type' => Consts::TASKING_TYPE_INTRO, 'level' => 2, 'quantity' => 50, 'currency' => Consts::CURRENCY_EXP],
            ['type' => Consts::TASKING_TYPE_INTRO, 'level' => 3, 'quantity' => 100, 'currency' => Consts::CURRENCY_EXP],
            ['type' => Consts::TASKING_TYPE_INTRO, 'level' => 4, 'quantity' => 10, 'currency' => Consts::CURRENCY_COIN],
            ['type' => Consts::TASKING_TYPE_DAILY, 'level' => 1, 'quantity' => 50, 'currency' => Consts::CURRENCY_EXP],
            ['type' => Consts::TASKING_TYPE_DAILY, 'level' => 2, 'quantity' => 50, 'currency' => Consts::CURRENCY_EXP],
            ['type' => Consts::TASKING_TYPE_DAILY, 'level' => 3, 'quantity' => 50, 'currency' => Consts::CURRENCY_EXP]
        ];

        foreach ($data as $value) {
            TaskingReward::create($value);
        }
    }
}
