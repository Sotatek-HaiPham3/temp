<?php

use App\Consts;
use App\Http\Services\MasterdataService;
use App\Models\Game;
use Illuminate\Database\Migrations\Migration;

class AddMasterDataJustChattingToGamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $baseQuery = Game::where('type', Consts::CATEGORY_TYPE_CHAT)->first();
        if (!$baseQuery) {
            Game::create([
                'title' => Consts::CHATTING_ROOM_CATEGORY_GAME_TITLE,
                'slug' => Consts::CHATTING_ROOM_CATEGORY_GAME_SLUG,
                'type' => Consts::CATEGORY_TYPE_CHAT,
                'logo' => env('WEB_APP_URL') . '/v2/just-chatting.logo.png',
                'portrait' => env('WEB_APP_URL') . '/v2/just-chatting.portrait.jpg',
                'banner' => env('WEB_APP_URL') . '/v2/just-chatting.banner.jpg'
            ]);
            MasterdataService::clearCacheOneTable('games');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Game::where('type',Consts::CATEGORY_TYPE_CHAT)->delete();
    }
}
