<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reason;
use App\Consts;
use DB;
use App\Http\Services\MasterdataService;

class GenerateDeclineCompletionReason extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reason:generate-decline-completion';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Session Decline Completion Reasons';

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
        $contents = array('Help me finish this game.', 'We still have time to play.', 'The game is not over yet.');

        DB::beginTransaction();
        try {
            foreach ($contents as $content) {
                Reason::insert([
                    'object_type'       => Consts::OBJECT_TYPE_SESSION,
                    'reason_type'       => Consts::REASON_TYPE_DECLINE,
                    'content'           => $content,
                    'static_reason'     => Consts::TRUE
                ]);
            }
            MasterdataService::clearCacheOneTable('reasons');
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            throw $ex;
        }
        
    }
}
