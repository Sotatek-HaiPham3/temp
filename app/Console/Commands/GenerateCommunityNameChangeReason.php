<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reason;
use App\Consts;
use DB;
use App\Http\Services\MasterdataService;

class GenerateCommunityNameChangeReason extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reason:generate-community-name-change';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Community Request name change reasons';

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
            $this->createCommunityNameChangeReason();
            MasterdataService::clearCacheOneTable('reasons');
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            throw $ex;
        }
    }

    private function createCommunityNameChangeReason()
    {
        DB::table('reasons')->where('object_type', Consts::OBJECT_TYPE_COMMUNITY_NAME_CHANGE)->delete();
        $contents = array(
            'Spelling error',
            'Changing purpose',
            'Other'
        );
        foreach ($contents as $content) {
            Reason::create([
                'object_type'       => Consts::OBJECT_TYPE_COMMUNITY_NAME_CHANGE,
                'reason_type'       => Consts::REASON_TYPE_REQUEST,
                'content'           => $content,
                'static_reason'     => Consts::TRUE
            ]);
        }
    }
}
