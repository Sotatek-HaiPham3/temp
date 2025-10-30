<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\GameProfile;
use App\Models\GameProfileOffer;
use App\Consts;
use DB;

class FixPriceForPreeGamelancer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'price-free-gamelancer:fix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix Price for Free Gamelancer';

    protected $fcmService;

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
            $userIds = User::where('user_type', Consts::USER_TYPE_FREE_GAMELANCER)
                ->pluck('id');

            $gameProfileIds = GameProfile::whereIn('user_id', $userIds)
                ->pluck('id');

            $data = [];
            foreach ($gameProfileIds as $gameProfileId) {

                $offer = GameProfileOffer::where('game_profile_id', $gameProfileId)
                    ->where('price', '>', 0)
                    ->first();

                if (!$offer) {
                    continue;
                }

                $offer->price = 0;
                $offer->save();

                $data[] = $gameProfileId;
            }

            logger()->info('===== size update: ',  [count($data)]);

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            throw $ex;
        }
    }
}
