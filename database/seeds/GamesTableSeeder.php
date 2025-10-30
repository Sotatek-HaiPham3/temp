<?php

use Illuminate\Database\Seeder;
use App\Models\Game;
use App\Consts;
use App\Utils;
use App\Console\Commands\GoogleSheetGameData;

class GamesTableSeeder extends Seeder
{

    private $models = [
        'games'
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $filename = GoogleSheetGameData::GAMES_DATA_FILE;
        $path = storage_path($filename);
        $json = json_decode(file_get_contents($path), true);

        $assetsUrl = Utils::getSchemeAndHttpHostForAssets();

        foreach ($json as $table => $tableData) {
            DB::table($table)->truncate();
            foreach ($tableData as $row) {
                if (in_array($table, $this->models)) {
                    $row['logo']        = $assetsUrl . DIRECTORY_SEPARATOR . $row['logo'];
                    $row['thumbnail']   = $assetsUrl . DIRECTORY_SEPARATOR . $row['thumbnail'];
                    $row['portrait']    = $assetsUrl . DIRECTORY_SEPARATOR . $row['portrait'];
                }
                DB::table($table)->insert($row);
            }
        }
    }
}
