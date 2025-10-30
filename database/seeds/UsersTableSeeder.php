<?php

use Illuminate\Database\Seeder;
use App\Consts;
use Faker\Factory as Faker;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->truncate();
        DB::table('user_balances')->truncate();
        DB::table('gamelancer_available_times')->truncate();
        DB::table('user_photos')->truncate();
        DB::table('user_social_networks')->truncate();
        DB::table('user_settings')->truncate();
        $this->createBots();
    }

    private function createBots()
    {
        $password = bcrypt('123123');

        $botCount = 10;
        for ($i = 1; $i < $botCount + 1; $i++) {
            $faker = Faker::create();
            $email = "bot$i@gmail.com";
            DB::table('users')->insert([
                'id'                        => $i,
                'email'                     => $email,
                'password'                  => $password,
                'username'                  => "bot{$i}",
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
            $this->createBalance($i);
            $this->createUserSettings($i);
            if ($i > 5) {
                $this->createAvailableTime($i);
                $this->createUserPhoto($i);
                $this->createUserSocialNetwork($i);
            }
        }
    }

    private function createBalance($userId)
    {
        DB::table('user_balances')->insert([
            'id'                    => $userId,
            'coin'                  => $userId <= 5 ? 1000000000 : 0,
            'bar'                   => $userId <= 5 ? 0 : 1000000000
        ]);
    }

    private function createUserSettings($userId)
    {
        DB::table('user_settings')->insert([
            'id'      => $userId
        ]);
    }

    private function createAvailableTime($userId)
    {
        for ($i=0; $i < 7; $i++) {
            DB::table('gamelancer_available_times')->insert([
                'user_id'           => $userId,
                'weekday'           => $i,
                'from'              => 600,
                'to'                => 900
            ]);
        }
    }

    private function createUserPhoto($userId)
    {
        DB::table('user_photos')->insert([
            'user_id'           => $userId,
            'url'               => 'https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcR7_Fo1olCl-psLUoEqePbtx_PA__E3hOWHHk1w7UIuk8WojzVG&usqp=CAU',
            'type'              => Consts::GAME_PROFILE_MEDIA_TYPE_IMAGE
        ]);
    }

    private function createUserSocialNetwork($userId)
    {
        DB::table('user_social_networks')->insert([
            [
                'user_id'           => $userId,
                'url'               => 'https://www.twitch.tv/',
                'type'              => 'twitch'
            ],
            [
                'user_id'           => $userId,
                'url'               => 'https://www.youtube.com/',
                'type'              => 'youtube'
            ]
        ]);
    }
}
