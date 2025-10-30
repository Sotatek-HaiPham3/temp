<?php

namespace App\Console\Commands;

use App\Consts;
use App\Models\Game;
use App\Models\RoomCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Http\Services\MasterdataService;

class UpdateGameID extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'game-id:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update to use game id in the database instead of magic number';

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
        DB::beginTransaction();
        try {
            $dataGame = Game::where('type', Consts::CATEGORY_TYPE_CHAT)->first();
            $gameId = $dataGame->id;
            // Update room_categories
            RoomCategory::whereIn('type', [Consts::CATEGORY_TYPE_CHAT, Consts::CATEGORY_TYPE_COMMUNITY])->update(['game_id' => $gameId, 'pinned' => Consts::TRUE]);
            MasterdataService::clearCacheOneTable('room_categories');

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            logger()->error($ex);
        }
    }
}
