<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Utils;
use App\Consts;
use App\Models\User;
use Faker\Factory as Faker;
use DB;
use App\Jobs\CreateMattermostUserEndpoint;
use App\Jobs\CreateNodebbUserEndpoint;
use Nodebb;
use App\Models\NodebbUser;
use App\Models\MattermostUser;
use Mattermost;

class FakeUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fake-users:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fake Users';

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
        if (Utils::isProduction()) {
            return;
        }

        DB::beginTransaction();
        try {
            $this->createBots();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    private function createBots()
    {
        $password = bcrypt('123123');
        $prefix   = 'glm_tester';

        $botCount = 50;
        $lastId = DB::table('users')->orderBy('id', 'desc')->value('id');

        for ($i = 1; $i < $botCount + 1; $i++) {
            $faker = Faker::create();
            $email = "{$prefix}{$i}@gmail.com";
            $username = "{$prefix}{$i}";
            $userId = $lastId + $i;

            DB::table('users')->insert([
                'id'                        => $userId,
                'email'                     => $email,
                'password'                  => $password,
                'username'                  => $username,
                'full_name'                 => $faker->name,
                'email_verified'            => Consts::TRUE,
                'level'                     => 0,
                'sex'                       => rand(0, 1),
                'user_type'                 => $i <= 5 ? 0 : 1,
                'dob'                       => $faker->dateTime(),
                'status'                    => Consts::USER_ACTIVE,
                'languages'                 => 'en',
                'description'               => $faker->text(),
                'remember_token'            => str_random(10)
            ]);
            $this->createBalance($userId);
            $this->createUserSettings($userId);
            DB::table('user_statistics')->insert(['user_id' => $userId]);
logger()->info("======= Created User: {$userId} - {$username}");
            // $this->createNodeBBUser($userId, $email, $username);
            $this->createMattermostUser($userId, $email, $username);

            // logger()->info("======= Created User: {$userId} - {$username}");
        }
    }

    private function createBalance($userId)
    {
        $baseline = 100000;
        DB::table('user_balances')->insert([
            'id'                    => $userId,
            'coin'                  => rand($baseline, $baseline * 10),
            'bar'                   => rand($baseline, $baseline * 10)
        ]);
    }

    private function createUserSettings($userId)
    {
        DB::table('user_settings')->insert([
            'id'      => $userId
        ]);
    }

    private function createNodeBBUser($userId, $email, $username)
    {
        $nodebbUser = Nodebb::createUserEndpoint($email, $username);

        NodebbUser::create([
            'user_id' => $userId,
            'nodebb_user_id' => $nodebbUser->payload->uid,
        ]);
    }

    private function createMattermostUser($userId, $email, $username)
    {
        $mattermostUser = Mattermost::createUserEndpoint($email, $username);

        MattermostUser::create([
            'user_id' => $userId,
            'mattermost_user_id' => $mattermostUser->id
        ]);
    }
}
