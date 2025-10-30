<?php

use Illuminate\Database\Seeder;
use App\Consts;

class BountySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->seedBounties();
        $this->seedBountyPlatforms();
        // $this->seedBountyServers();
    }

    private function seedBounties()
    {
        DB::table('bounties')->truncate();
        DB::table('bounties')->insert([
            [
                'user_id'               => 1,
                'game_id'               => 1,
                'price'                 => 10,
                'title'                 => 'Help Me',
                'slug'                  => 'help-me',
                'description'           => 'It is a long established fact that a reader will be distracted by the readable content of a page when looking at its',
                'escrow_balance'        => 10,
                'media'                 => 'https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcTeZarVT84nl2-4W5v2WXJOSiqq7cWFhUQEHdQTIo7NsMSJG6zC&usqp=CAU',
                // 'rank_id'               => 1,
                // 'user_level_meta_id'    => 1,
                'status'                => Consts::BOUNTY_STATUS_CREATED
            ],
            [
                'user_id'               => 1,
                'game_id'               => 2,
                'price'                 => 20,
                'title'                 => 'This Game Is So Hard',
                'slug'                  => 'this-game-is-so-hard',
                'description'           => 'It is a long established fact that a reader will be distracted by the readable content of a page when looking at its',
                'escrow_balance'        => 20,
                'media'                 => 'https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcTeZarVT84nl2-4W5v2WXJOSiqq7cWFhUQEHdQTIo7NsMSJG6zC&usqp=CAU',
                // 'rank_id'               => 9,
                // 'user_level_meta_id'    => 1,
                'status'                => Consts::BOUNTY_STATUS_CREATED
            ],
            [
                'user_id'               => 2,
                'game_id'               => 2,
                'price'                 => 30,
                'title'                 => 'Help Me Win This Prize',
                'slug'                  => 'help-me-win-this-prize',
                'description'           => 'It is a long established fact that a reader will be distracted by the readable content of a page when looking at its',
                'escrow_balance'        => 30,
                'media'                 => 'https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcTeZarVT84nl2-4W5v2WXJOSiqq7cWFhUQEHdQTIo7NsMSJG6zC&usqp=CAU',
                // 'rank_id'               => 10,
                // 'user_level_meta_id'    => 2,
                'status'                => Consts::BOUNTY_STATUS_CREATED
            ],
            [
                'user_id'               => 2,
                'game_id'               => 4,
                'price'                 => 40,
                'title'                 => 'Let Play With Me',
                'slug'                  => 'let-play-with-me',
                'description'           => 'It is a long established fact that a reader will be distracted by the readable content of a page when looking at its',
                'escrow_balance'        => 40,
                'media'                 => 'https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcTeZarVT84nl2-4W5v2WXJOSiqq7cWFhUQEHdQTIo7NsMSJG6zC&usqp=CAU',
                // 'rank_id'               => 210,
                // 'user_level_meta_id'    => 2,
                'status'                => Consts::BOUNTY_STATUS_CREATED
            ],
            [
                'user_id'               => 3,
                'game_id'               => 4,
                'price'                 => 50,
                'title'                 => 'Join My Team',
                'slug'                  => 'join-my-team',
                'description'           => 'It is a long established fact that a reader will be distracted by the readable content of a page when looking at its',
                'escrow_balance'        => 50,
                'media'                 => 'https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcTeZarVT84nl2-4W5v2WXJOSiqq7cWFhUQEHdQTIo7NsMSJG6zC&usqp=CAU',
                // 'rank_id'               => 215,
                // 'user_level_meta_id'    => 3,
                'status'                => Consts::BOUNTY_STATUS_CREATED
            ],
            [
                'user_id'               => 3,
                'game_id'               => 6,
                'price'                 => 60,
                'title'                 => 'Fight With Me',
                'slug'                  => 'fight-with-me',
                'description'           => 'It is a long established fact that a reader will be distracted by the readable content of a page when looking at its',
                'escrow_balance'        => 60,
                'media'                 => 'https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcTeZarVT84nl2-4W5v2WXJOSiqq7cWFhUQEHdQTIo7NsMSJG6zC&usqp=CAU',
                // 'rank_id'               => 220,
                // 'user_level_meta_id'    => 3,
                'status'                => Consts::BOUNTY_STATUS_CREATED
            ]
        ]);
    }

    private function seedBountyPlatforms()
    {
        DB::table('bounty_platforms')->truncate();
        DB::table('bounty_platforms')->insert([
            [
                'bounty_id'     => 1,
                'platform_id'   => 1
            ],
            [
                'bounty_id'     => 2,
                'platform_id'   => 2
            ],
            [
                'bounty_id'     => 3,
                'platform_id'   => 2
            ],
            [
                'bounty_id'     => 4,
                'platform_id'   => 5
            ],
            [
                'bounty_id'     => 5,
                'platform_id'   => 5
            ],
            [
                'bounty_id'     => 6,
                'platform_id'   => 14
            ]
        ]);
    }

    private function seedBountyServers()
    {
        DB::table('bounty_servers')->truncate();
        DB::table('bounty_servers')->insert([
            [
                'bounty_id'         => 1,
                'game_server_id'    => 1
            ],
            [
                'bounty_id'         => 1,
                'game_server_id'    => 2
            ],
            [
                'bounty_id'         => 1,
                'game_server_id'    => 3
            ],
            [
                'bounty_id'         => 2,
                'game_server_id'    => 18
            ],
            [
                'bounty_id'         => 2,
                'game_server_id'    => 19
            ],
            [
                'bounty_id'         => 3,
                'game_server_id'    => 18
            ],
            [
                'bounty_id'         => 3,
                'game_server_id'    => 19
            ],
            [
                'bounty_id'         => 3,
                'game_server_id'    => 20
            ],
            [
                'bounty_id'         => 4,
                'game_server_id'    => 26
            ],
            [
                'bounty_id'         => 4,
                'game_server_id'    => 27
            ],
            [
                'bounty_id'         => 4,
                'game_server_id'    => 28
            ],
            [
                'bounty_id'         => 5,
                'game_server_id'    => 26
            ],
            [
                'bounty_id'         => 5,
                'game_server_id'    => 27
            ],
            [
                'bounty_id'         => 5,
                'game_server_id'    => 28
            ],
            [
                'bounty_id'         => 6,
                'game_server_id'    => 40
            ],
            [
                'bounty_id'         => 6,
                'game_server_id'    => 41
            ],
            [
                'bounty_id'         => 6,
                'game_server_id'    => 42
            ]
        ]);
    }
}
