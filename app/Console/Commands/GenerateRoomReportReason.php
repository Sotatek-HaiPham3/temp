<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reason;
use App\Consts;
use DB;
use App\Http\Services\MasterdataService;

class GenerateRoomReportReason extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reason:generate-room-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Room Report Reasons';

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
            $this->createRoomReportReason();
            $this->createUserReportReason();
            MasterdataService::clearCacheOneTable('reasons');
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            throw $ex;
        }
    }

    private function createRoomReportReason()
    {
        $contents = array(
            'Discrimination or hateful speech',
            'Harrasement or repulsive language',
            'Unreasonable demands',
            'Incorrect information'
        );
        foreach ($contents as $content) {
            Reason::insert([
                'object_type'       => Consts::OBJECT_TYPE_ROOM,
                'reason_type'       => Consts::REASON_TYPE_REPORT,
                'content'           => $content,
                'static_reason'     => Consts::TRUE
            ]);
        }
    }

    private function createUserReportReason()
    {
        $contents = array(
            'Discrimination or hateful speech',
            'Harrasement or repulsive language',
            'Unreasonable demands',
            'Incorrect information'
        );
        foreach ($contents as $content) {
            Reason::insert([
                'object_type'       => Consts::OBJECT_TYPE_USER,
                'reason_type'       => Consts::REASON_TYPE_REPORT,
                'content'           => $content,
                'static_reason'     => Consts::TRUE
            ]);
        }
    }
}
