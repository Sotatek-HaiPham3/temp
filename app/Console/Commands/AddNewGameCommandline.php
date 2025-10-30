<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Console\Commands\GoogleSheetGameData;
use App\Utils;
use DB;

class AddNewGameCommandline extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'game:add {game_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add new Game and related assets';

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
        if (Utils::isProduction() && !$this->confirm('Do you wish to continue?')) {
            return;
        }

        $gameId = $this->argument('game_id');

        $filename = GoogleSheetGameData::GAMES_DATA_FILE;
        $path = storage_path($filename);
        $json = json_decode(file_get_contents($path), true);

        // $assetsUrl = Utils::getSchemeAndHttpHostForAssets();
        // Currently it hasn't use the cloudfront/caching yet.
        $assetsUrl = env('APP_URL');

        foreach ($json as $table => $tableData) {
            foreach ($tableData as $row) {

                if (!in_array($table, ['games', 'game_types', 'game_platforms'])) {
                    continue;
                }

                $isGamesTable = $table === 'games';

                $fieldCondition = $isGamesTable ? 'id' : 'game_id';

                if ($row[$fieldCondition] !== $gameId) {
                    continue;
                }

                if ($isGamesTable) {
                    $row['logo']        = $assetsUrl . DIRECTORY_SEPARATOR . $row['logo'];
                    $row['thumbnail']   = $assetsUrl . DIRECTORY_SEPARATOR . $row['thumbnail'];
                    $row['portrait']    = $assetsUrl . DIRECTORY_SEPARATOR . $row['portrait'];
                }

                $row['created_at'] = now();
                $row['updated_at'] = now();

                DB::table($table)->insert($row);
            }
        }
    }
}
