<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Services\MasterdataService;
use App\Models\Setting;
use App\Consts;
use Nodebb;
use DB;

class CreateNodebbCategory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nodebb-category-create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create nodebb category';

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
            $categories = [
                'nodebb.categories.post.name' => Consts::NODEBB_CATEGORY_POST_ID_KEY, // Create category for my post
                'nodebb.categories.video.name' => Consts::NODEBB_CATEGORY_VIDEO_ID_KEY, // Create category for my video
            ];

            collect($categories)->each(function ($category, $key) {
                $value = Nodebb::createCategory($category, config($key));
                if (!empty($value)) {
                    Setting::create([
                        'key'       => $category,
                        'value'     => $value
                    ]);
                }
            });

            DB::commit();

            MasterdataService::clearCacheOneTable('settings');
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
