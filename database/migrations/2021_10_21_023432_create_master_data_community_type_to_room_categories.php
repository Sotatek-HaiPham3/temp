<?php

use App\Consts;
use App\Http\Services\MasterdataService;
use App\Models\RoomCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateMasterDataCommunityTypeToRoomCategories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $baseQuery = RoomCategory::where('game_id', Consts::COMMUNITY_ROOM_CATEGORY_GAME_ID)->withTrashed()->first();
        if (!$baseQuery) {
            RoomCategory::create([
                'game_id'       => Consts::COMMUNITY_ROOM_CATEGORY_GAME_ID,
                'type'          => Consts::CATEGORY_TYPE_COMMUNITY,
                'size_range'    => Consts::COMMUNITY_VOICE_ROOM_SIZE,
                'label'         => 'Group Community',
                'image'         => 'https://img.chdrstatic.com/media/a7df1689-b00e-4c67-9fd4-8fbd6f0d79a2.jpg',
                'description'   => 'You can Start a Group to hangout with friends or run a community of people with same interests as you!',
                'is_public' => Consts::FALSE
            ]);

            MasterdataService::clearCacheOneTable('room_categories');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        RoomCategory::where('game_id', Consts::COMMUNITY_ROOM_CATEGORY_GAME_ID)->withTrashed()->forceDelete();
    }
}
