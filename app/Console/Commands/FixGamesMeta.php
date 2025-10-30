<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Utils;
use App\Http\Services\MasterdataService;
use App\Models\Game;

class FixGamesMeta extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'games:fix-asset-url';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert from URL server to URL CloudFront';

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
        $awsUrl = rtrim(env('AWS_URL', null), DIRECTORY_SEPARATOR);

        if (empty($awsUrl) || (Utils::isProduction() && !$this->confirm('Do you wish to continue?'))) {
            return;
        }

        Game::all()->each(function ($model) use ($awsUrl) {
            $shouldUpdate = !Str::contains($model->logo, $awsUrl)
                || !Str::contains($model->thumbnail, $awsUrl)
                || !Str::contains($model->portrait, $awsUrl);

            if (!$shouldUpdate) {
                return;
            }

            $model->logo = $this->updateAssetUrl($model->logo, $awsUrl);
            $model->thumbnail = $this->updateAssetUrl($model->thumbnail, $awsUrl);
            $model->portrait = $this->updateAssetUrl($model->portrait, $awsUrl);

            $model->save();
        });

        MasterdataService::clearCacheOneTable('games');
    }

    private function updateAssetUrl($url, $awsUrl)
    {
        $path = parse_url($url, PHP_URL_PATH);
        $newPath = Str::replaceFirst('/images/games', '/data/g-origin', $path);

        return sprintf('%s%s', $awsUrl, $newPath);

    }
}
