<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Utils;
use App\Consts;
use App\Models\UserSocialNetwork;
use Exception;
use DB;

class FixUserSocialLink extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user_social_link:fix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixing User Social Link';

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

        DB::beginTransaction();
        try {
            $validIds = $this->processValidSocialLinks();
            $remainingIds = $this->tryProcessingLinks($validIds);
            // echo $remainingIds;
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            logger()->error($ex);
        }
    }

    private function processValidSocialLinks()
    {
        $socialList = array_keys(Consts::SOCIAL_NETWORKS_LINK);
        $links = UserSocialNetwork::whereNull('social_id')
            ->whereIn('type', $socialList)
            ->get();

        return $links->map(function ($record) {
            $record->social_id = $this->parseSocialId($record->url);
            $record->save();

            return $record->id;
        })->values();
    }

    private function tryProcessingLinks($ids)
    {
        $data = UserSocialNetwork::whereNotIn('id', $ids)->get();

        $socialList = array_keys(Consts::SOCIAL_NETWORKS_LINK);
        $remainingIds = [];

        foreach ($data as $record) {
            $filtered = collect($socialList)->filter(function ($item) use ($record) {
                return Str::contains(Str::lower($record->url), $item);
            });

            if ($filtered->isEmpty()) {
                $remainingIds[] = $record->id;
                continue;
            }

            $socialId = $this->parseSocialId($record->url);

            if (empty($socialId)) {
                $remainingIds[] = $record->id;
                continue;
            }

            $record->type = $filtered->first();
            $record->social_id = $socialId;
            $record->save();
        }

        return $remainingIds;
    }

    private function parseSocialId($url)
    {
        $path = @parse_url($url, PHP_URL_PATH);
        $path = Utils::trimChar($path);
        return empty($path) ? null : $path;
    }
}
