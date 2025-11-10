<?php

use Illuminate\Database\Seeder;
use App\Consts;
use App\Utils;

class GameProfilesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->seedGameProfiles();
    }

    private function seedGameProfiles()
    {
        DB::table('game_profiles')->truncate();
        $id1 = DB::table('game_profiles')->insertGetId([
            'user_id'               => 6,
            'game_id'               => 1,
            // 'rank_id'               => 1
        ]);

        $id2 = DB::table('game_profiles')->insertGetId([
            'user_id'               => 6,
            'game_id'               => 3,
            // 'rank_id'               => 209
        ]);

        $id3 = DB::table('game_profiles')->insertGetId([
            'user_id'               => 7,
            'game_id'               => 1,
            // 'rank_id'               => 1
        ]);

        $id4 = DB::table('game_profiles')->insertGetId([
            'user_id'               => 7,
            'game_id'               => 3,
            // 'rank_id'               => 209
        ]);

        $id5 = DB::table('game_profiles')->insertGetId([
            'user_id'               => 8,
            'game_id'               => 1,
            // 'rank_id'               => 1
        ]);

        $id6 = DB::table('game_profiles')->insertGetId([
            'user_id'               => 8,
            'game_id'               => 3,
            // 'rank_id'               => 209
        ]);

        $this->seedGameProfileOffers($id1, $id2, $id3, $id4, $id5, $id6);
        // $this->seedGameProfileMatchServers($id1, $id2, $id3, $id4, $id5, $id6);
        $this->seedGameProfilePlatforms($id1, $id2, $id3, $id4, $id5, $id6);
        $this->seedGameProfileMedias($id1, $id2, $id3, $id4, $id5, $id6);
        $this->seedReviews($id1, $id2, $id3, $id4, $id5, $id6);
    }

    private function seedGameProfileOffers($id1, $id2, $id3, $id4, $id5, $id6)
    {
        DB::table('game_profile_offers')->truncate();
        DB::table('game_profile_offers')->insert([
            [
                'game_profile_id'       => $id1,
                'type'                  => 'per_game',
                'quantity'              => 1,
                'price'                 => 10
            ],
            [
                'game_profile_id'       => $id2,
                'type'                  => 'hour',
                'quantity'              => 1,
                'price'                 => 5
            ],
            [
                'game_profile_id'       => $id3,
                'type'                  => 'per_game',
                'quantity'              => 1,
                'price'                 => 10
            ],
            [
                'game_profile_id'       => $id4,
                'type'                  => 'hour',
                'quantity'              => 1,
                'price'                 => 5
            ],
            [
                'game_profile_id'       => $id5,
                'type'                  => 'per_game',
                'quantity'              => 1,
                'price'                 => 5
            ],
            [
                'game_profile_id'       => $id6,
                'type'                  => 'hour',
                'quantity'              => 1,
                'price'                 => 10
            ]
        ]);
    }

    private function seedGameProfileMatchServers($id1, $id2, $id3, $id4, $id5, $id6)
    {
        DB::table('game_profile_match_servers')->truncate();
        DB::table('game_profile_match_servers')->insert([
            [
                'game_profile_id'       => $id1,
                'game_server_id'        => 1
            ],
            [
                'game_profile_id'       => $id1,
                'game_server_id'        => 2
            ],
            [
                'game_profile_id'       => $id1,
                'game_server_id'        => 3
            ],
            [
                'game_profile_id'       => $id2,
                'game_server_id'        => 25
            ],
            [
                'game_profile_id'       => $id3,
                'game_server_id'        => 4
            ],
            [
                'game_profile_id'       => $id3,
                'game_server_id'        => 5
            ],
            [
                'game_profile_id'       => $id3,
                'game_server_id'        => 6
            ],
            [
                'game_profile_id'       => $id4,
                'game_server_id'        => 25
            ],
            [
                'game_profile_id'       => $id5,
                'game_server_id'        => 1
            ],
            [
                'game_profile_id'       => $id5,
                'game_server_id'        => 2
            ],
            [
                'game_profile_id'       => $id5,
                'game_server_id'        => 3
            ],
            [
                'game_profile_id'       => $id6,
                'game_server_id'        => 25
            ]
        ]);
    }

    private function seedGameProfilePlatforms($id1, $id2, $id3, $id4, $id5, $id6)
    {
        DB::table('game_profile_platforms')->truncate();
        DB::table('game_profile_platforms')->insert([
            [
                'game_profile_id'   => $id1,
                'platform_id'       => 1
            ],
            [
                'game_profile_id'   => $id2,
                'platform_id'       => 2
            ],
            [
                'game_profile_id'   => $id3,
                'platform_id'       => 1
            ],
            [
                'game_profile_id'   => $id4,
                'platform_id'       => 2
            ],
            [
                'game_profile_id'   => $id5,
                'platform_id'       => 1
            ],
            [
                'game_profile_id'   => $id6,
                'platform_id'       => 2
            ]
        ]);
    }

    private function seedGameProfileMedias($id1, $id2, $id3, $id4, $id5, $id6)
    {
        DB::table('game_profile_medias')->truncate();
        DB::table('game_profile_medias')->insert([
            [
                'game_profile_id'   => $id1,
                'url'               => 'https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcTeZarVT84nl2-4W5v2WXJOSiqq7cWFhUQEHdQTIo7NsMSJG6zC&usqp=CAU',
                'type'              => 'image'
            ],
            [
                'game_profile_id'   => $id1,
                'url'               => 'https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcTeZarVT84nl2-4W5v2WXJOSiqq7cWFhUQEHdQTIo7NsMSJG6zC&usqp=CAU',
                'type'              => 'image'
            ],
            [
                'game_profile_id'   => $id1,
                'url'               => 'https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcTeZarVT84nl2-4W5v2WXJOSiqq7cWFhUQEHdQTIo7NsMSJG6zC&usqp=CAU',
                'type'              => 'image'
            ],
            [
                'game_profile_id'   => $id2,
                'url'               => 'https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcTeZarVT84nl2-4W5v2WXJOSiqq7cWFhUQEHdQTIo7NsMSJG6zC&usqp=CAU',
                'type'              => 'image'
            ],
            [
                'game_profile_id'   => $id2,
                'url'               => 'https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcTeZarVT84nl2-4W5v2WXJOSiqq7cWFhUQEHdQTIo7NsMSJG6zC&usqp=CAU',
                'type'              => 'image'
            ],
            [
                'game_profile_id'   => $id2,
                'url'               => 'https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcTeZarVT84nl2-4W5v2WXJOSiqq7cWFhUQEHdQTIo7NsMSJG6zC&usqp=CAU',
                'type'              => 'image'
            ],
            [
                'game_profile_id'   => $id2,
                'url'               => 'https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcTeZarVT84nl2-4W5v2WXJOSiqq7cWFhUQEHdQTIo7NsMSJG6zC&usqp=CAU',
                'type'              => 'image'
            ],
            [
                'game_profile_id'   => $id2,
                'url'               => 'https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcTeZarVT84nl2-4W5v2WXJOSiqq7cWFhUQEHdQTIo7NsMSJG6zC&usqp=CAU',
                'type'              => 'image'
            ],
            [
                'game_profile_id'   => $id3,
                'url'               => 'https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcTeZarVT84nl2-4W5v2WXJOSiqq7cWFhUQEHdQTIo7NsMSJG6zC&usqp=CAU',
                'type'              => 'image'
            ],
            [
                'game_profile_id'   => $id3,
                'url'               => 'https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcTeZarVT84nl2-4W5v2WXJOSiqq7cWFhUQEHdQTIo7NsMSJG6zC&usqp=CAU',
                'type'              => 'image'
            ],
            [
                'game_profile_id'   => $id3,
                'url'               => 'https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcTeZarVT84nl2-4W5v2WXJOSiqq7cWFhUQEHdQTIo7NsMSJG6zC&usqp=CAU',
                'type'              => 'image'
            ],
            [
                'game_profile_id'   => $id4,
                'url'               => 'https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcTeZarVT84nl2-4W5v2WXJOSiqq7cWFhUQEHdQTIo7NsMSJG6zC&usqp=CAU',
                'type'              => 'image'
            ],
            [
                'game_profile_id'   => $id4,
                'url'               => 'https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcTeZarVT84nl2-4W5v2WXJOSiqq7cWFhUQEHdQTIo7NsMSJG6zC&usqp=CAU',
                'type'              => 'image'
            ],
            [
                'game_profile_id'   => $id4,
                'url'               => 'https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcTeZarVT84nl2-4W5v2WXJOSiqq7cWFhUQEHdQTIo7NsMSJG6zC&usqp=CAU',
                'type'              => 'image'
            ],
            [
                'game_profile_id'   => $id5,
                'url'               => 'https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcTeZarVT84nl2-4W5v2WXJOSiqq7cWFhUQEHdQTIo7NsMSJG6zC&usqp=CAU',
                'type'              => 'image'
            ],
            [
                'game_profile_id'   => $id5,
                'url'               => 'https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcTeZarVT84nl2-4W5v2WXJOSiqq7cWFhUQEHdQTIo7NsMSJG6zC&usqp=CAU',
                'type'              => 'image'
            ],
            [
                'game_profile_id'   => $id5,
                'url'               => 'https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcTeZarVT84nl2-4W5v2WXJOSiqq7cWFhUQEHdQTIo7NsMSJG6zC&usqp=CAU',
                'type'              => 'image'
            ],
            [
                'game_profile_id'   => $id6,
                'url'               => 'https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcTeZarVT84nl2-4W5v2WXJOSiqq7cWFhUQEHdQTIo7NsMSJG6zC&usqp=CAU',
                'type'              => 'image'
            ],
            [
                'game_profile_id'   => $id6,
                'url'               => 'https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcTeZarVT84nl2-4W5v2WXJOSiqq7cWFhUQEHdQTIo7NsMSJG6zC&usqp=CAU',
                'type'              => 'image'
            ],
            [
                'game_profile_id'   => $id6,
                'url'               => 'https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcTeZarVT84nl2-4W5v2WXJOSiqq7cWFhUQEHdQTIo7NsMSJG6zC&usqp=CAU',
                'type'              => 'image'
            ],
            [
                'game_profile_id'   => $id6,
                'url'               => 'https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcTeZarVT84nl2-4W5v2WXJOSiqq7cWFhUQEHdQTIo7NsMSJG6zC&usqp=CAU',
                'type'              => 'image'
            ]
        ]);
    }

    private function seedReviews($id1, $id2, $id3, $id4, $id5, $id6)
    {
        DB::table('session_reviews')->truncate();
        $now = now();
        $submitAt = Utils::currentMilliseconds();
        DB::table('session_reviews')->insert([
            [
                'object_id'         => 0,
                'game_profile_id'   => $id1,
                'reviewer_id'       => 1,
                'user_id'           => 6,
                'object_type'       => Consts::OBJECT_TYPE_SESSION,
                'rate'              => 3,
                'description'       => "It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using 'Content here, content here', making it look like readable English. Many desktop publishing packages and web page editors now use Lorem Ipsum as their default model text, and a search for 'lorem ipsum' will uncover many web sites still in their infancy. Various versions have evolved over the years, sometimes by accident, sometimes on purpose (injected humour and the like).",
                'submit_at'         => $submitAt,
                'created_at'        => $now
            ],
            [
                'object_id'         => 0,
                'game_profile_id'   => $id1,
                'reviewer_id'       => 2,
                'user_id'           => 6,
                'object_type'       => Consts::OBJECT_TYPE_SESSION,
                'rate'              => 4,
                'description'       => "It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using 'Content here, content here', making it look like readable English. Many desktop publishing packages and web page editors now use Lorem Ipsum as their default model text, and a search for 'lorem ipsum' will uncover many web sites still in their infancy. Various versions have evolved over the years, sometimes by accident, sometimes on purpose (injected humour and the like).",
                'submit_at'         => $submitAt,
                'created_at'        => $now
            ],
            [
                'object_id'         => 0,
                'game_profile_id'   => $id1,
                'reviewer_id'       => 3,
                'user_id'           => 6,
                'object_type'       => Consts::OBJECT_TYPE_SESSION,
                'rate'              => 5,
                'description'       => 'Very nice! Would play again',
                'submit_at'         => $submitAt,
                'created_at'        => $now
            ],
            [
                'object_id'         => 0,
                'game_profile_id'   => $id1,
                'reviewer_id'       => 4,
                'user_id'           => 6,
                'object_type'       => Consts::OBJECT_TYPE_SESSION,
                'rate'              => 4,
                'description'       => 'Very nice! Would play again',
                'submit_at'         => $submitAt,
                'created_at'        => $now
            ],
            [
                'object_id'         => 0,
                'game_profile_id'   => $id2,
                'reviewer_id'       => 1,
                'user_id'           => 6,
                'object_type'       => Consts::OBJECT_TYPE_SESSION,
                'rate'              => 3,
                'description'       => 'Very nice! Would play again',
                'submit_at'         => $submitAt,
                'created_at'        => $now
            ],
            [
                'object_id'         => 0,
                'game_profile_id'   => $id2,
                'reviewer_id'       => 2,
                'user_id'           => 6,
                'object_type'       => Consts::OBJECT_TYPE_SESSION,
                'rate'              => 4,
                'description'       => 'Very nice! Would play again',
                'submit_at'         => $submitAt,
                'created_at'        => $now
            ],
            [
                'object_id'         => 0,
                'game_profile_id'   => $id2,
                'reviewer_id'       => 3,
                'user_id'           => 6,
                'object_type'       => Consts::OBJECT_TYPE_SESSION,
                'rate'              => 5,
                'description'       => 'Very nice! Would play again',
                'submit_at'         => $submitAt,
                'created_at'        => $now
            ],
            [
                'object_id'         => 0,
                'game_profile_id'   => $id2,
                'reviewer_id'       => 4,
                'user_id'           => 6,
                'object_type'       => Consts::OBJECT_TYPE_SESSION,
                'rate'              => 4,
                'description'       => 'Very nice! Would play again',
                'submit_at'         => $submitAt,
                'created_at'        => $now
            ],
            [
                'object_id'         => 0,
                'game_profile_id'   => $id3,
                'reviewer_id'       => 1,
                'user_id'           => 7,
                'object_type'       => Consts::OBJECT_TYPE_SESSION,
                'rate'              => 3,
                'description'       => 'Very nice! Would play again',
                'submit_at'         => $submitAt,
                'created_at'        => $now
            ],
            [
                'object_id'         => 0,
                'game_profile_id'   => $id3,
                'reviewer_id'       => 2,
                'user_id'           => 7,
                'object_type'       => Consts::OBJECT_TYPE_SESSION,
                'rate'              => 4,
                'description'       => 'Very nice! Would play again',
                'submit_at'         => $submitAt,
                'created_at'        => $now
            ],
            [
                'object_id'         => 0,
                'game_profile_id'   => $id3,
                'reviewer_id'       => 3,
                'user_id'           => 7,
                'object_type'       => Consts::OBJECT_TYPE_SESSION,
                'rate'              => 5,
                'description'       => 'Very nice! Would play again',
                'submit_at'         => $submitAt,
                'created_at'        => $now
            ],
            [
                'object_id'         => 0,
                'game_profile_id'   => $id3,
                'reviewer_id'       => 4,
                'user_id'           => 7,
                'object_type'       => Consts::OBJECT_TYPE_SESSION,
                'rate'              => 4,
                'description'       => 'Very nice! Would play again',
                'submit_at'         => $submitAt,
                'created_at'        => $now
            ],
            [
                'object_id'         => 0,
                'game_profile_id'   => $id4,
                'reviewer_id'       => 1,
                'user_id'           => 7,
                'object_type'       => Consts::OBJECT_TYPE_SESSION,
                'rate'              => 3,
                'description'       => 'Very nice! Would play again',
                'submit_at'         => $submitAt,
                'created_at'        => $now
            ],
            [
                'object_id'         => 0,
                'game_profile_id'   => $id4,
                'reviewer_id'       => 2,
                'user_id'           => 7,
                'object_type'       => Consts::OBJECT_TYPE_SESSION,
                'rate'              => 4,
                'description'       => 'Very nice! Would play again',
                'submit_at'         => $submitAt,
                'created_at'        => $now
            ],
            [
                'object_id'         => 0,
                'game_profile_id'   => $id4,
                'reviewer_id'       => 3,
                'user_id'           => 7,
                'object_type'       => Consts::OBJECT_TYPE_SESSION,
                'rate'              => 5,
                'description'       => 'Very nice! Would play again',
                'submit_at'         => $submitAt,
                'created_at'        => $now
            ],
            [
                'object_id'         => 0,
                'game_profile_id'   => $id4,
                'reviewer_id'       => 4,
                'user_id'           => 7,
                'object_type'       => Consts::OBJECT_TYPE_SESSION,
                'rate'              => 4,
                'description'       => 'Very nice! Would play again',
                'submit_at'         => $submitAt,
                'created_at'        => $now
            ],
            [
                'object_id'         => 0,
                'game_profile_id'   => $id5,
                'reviewer_id'       => 1,
                'user_id'           => 8,
                'object_type'       => Consts::OBJECT_TYPE_SESSION,
                'rate'              => 3,
                'description'       => 'Very nice! Would play again',
                'submit_at'         => $submitAt,
                'created_at'        => $now
            ],
            [
                'object_id'         => 0,
                'game_profile_id'   => $id5,
                'reviewer_id'       => 2,
                'user_id'           => 8,
                'object_type'       => Consts::OBJECT_TYPE_SESSION,
                'rate'              => 4,
                'description'       => 'Very nice! Would play again',
                'submit_at'         => $submitAt,
                'created_at'        => $now
            ],
            [
                'object_id'         => 0,
                'game_profile_id'   => $id5,
                'reviewer_id'       => 3,
                'user_id'           => 8,
                'object_type'       => Consts::OBJECT_TYPE_SESSION,
                'rate'              => 5,
                'description'       => 'Very nice! Would play again',
                'submit_at'         => $submitAt,
                'created_at'        => $now
            ],
            [
                'object_id'         => 0,
                'game_profile_id'   => $id5,
                'reviewer_id'       => 4,
                'user_id'           => 8,
                'object_type'       => Consts::OBJECT_TYPE_SESSION,
                'rate'              => 4,
                'description'       => 'Very nice! Would play again',
                'submit_at'         => $submitAt,
                'created_at'        => $now
            ],
            [
                'object_id'         => 0,
                'game_profile_id'   => $id6,
                'reviewer_id'       => 1,
                'user_id'           => 8,
                'object_type'       => Consts::OBJECT_TYPE_SESSION,
                'rate'              => 3,
                'description'       => 'Very nice! Would play again',
                'submit_at'         => $submitAt,
                'created_at'        => $now
            ],
            [
                'object_id'         => 0,
                'game_profile_id'   => $id6,
                'reviewer_id'       => 2,
                'user_id'           => 8,
                'object_type'       => Consts::OBJECT_TYPE_SESSION,
                'rate'              => 4,
                'description'       => 'Very nice! Would play again',
                'submit_at'         => $submitAt,
                'created_at'        => $now
            ],
            [
                'object_id'         => 0,
                'game_profile_id'   => $id6,
                'reviewer_id'       => 3,
                'user_id'           => 8,
                'object_type'       => Consts::OBJECT_TYPE_SESSION,
                'rate'              => 5,
                'description'       => 'Very nice! Would play again',
                'submit_at'         => $submitAt,
                'created_at'        => $now
            ],
            [
                'object_id'         => 0,
                'game_profile_id'   => $id6,
                'reviewer_id'       => 4,
                'user_id'           => 8,
                'object_type'       => Consts::OBJECT_TYPE_SESSION,
                'rate'              => 4,
                'description'       => 'Very nice! Would play again',
                'submit_at'         => $submitAt,
                'created_at'        => $now
            ]
        ]);
    }
}
