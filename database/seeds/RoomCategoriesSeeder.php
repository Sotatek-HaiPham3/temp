<?php

use Illuminate\Database\Seeder;
use App\Models\RoomCategory;
use App\Consts;

class RoomCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('room_categories')->truncate();

        RoomCategory::create([
            'game_id'       => Consts::CHATTING_ROOM_CATEGORY_GAME_ID,
            'type'          => Consts::CATEGORY_TYPE_CHAT,
            'size_range'    => '10,30,50,100',
            'label'         => 'Just Chatting',
            'image'         => 'https://img.chdrstatic.com/media/a7df1689-b00e-4c67-9fd4-8fbd6f0d79a2.jpg',
            'description'   => 'You can use in-game username, in-game code to invite friends to play with. Let’s have fun together!'
        ]);
    }
}
