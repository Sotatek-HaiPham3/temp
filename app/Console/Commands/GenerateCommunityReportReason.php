<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reason;
use App\Consts;
use DB;
use App\Http\Services\MasterdataService;

class GenerateCommunityReportReason extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reason:generate-community-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate community report reasons';

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
            $this->createCommunityReportReason();
            MasterdataService::clearCacheOneTable('reasons');
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            throw $ex;
        }
    }

    private function createCommunityReportReason()
    {
        DB::table('reasons')->where('object_type', Consts::OBJECT_TYPE_COMMUNITY)->delete();
        $contents = array(
            'Discrimination or hateful speech',
            'Harassment or repulsive language',
            'Unreasonable demands',
            'Incorrect information'
        );
        foreach ($contents as $content) {
            Reason::create([
                'object_type'       => Consts::OBJECT_TYPE_COMMUNITY,
                'reason_type'       => Consts::REASON_TYPE_REPORT,
                'content'           => $content,
                'static_reason'     => Consts::TRUE
            ]);
        }
    }
}
