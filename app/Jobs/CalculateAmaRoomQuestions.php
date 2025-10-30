<?php

namespace App\Jobs;

use App\Events\CalculateRoomQuestionUpdated;
use App\Http\Services\VoiceService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateAmaRoomQuestions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $roomId;
    private $voiceService;

    /**
     * Create a new job instance.
     *
     * @param $communityId
     */
    public function __construct($roomId)
    {
        $this->roomId = $roomId;
        $this->voiceService = new VoiceService();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $data = $this->voiceService->countQuestions($this->roomId);
            $custom = collect(['room_id' => $this->roomId]);
            $dataCustom = $custom->merge($data);
            event(new CalculateRoomQuestionUpdated($dataCustom));

            return;
        } catch (Exception $exception) {
            $this->log($exception);
        }
    }

    private function log(...$params)
    {
        logger('==========CalculateAmaRoomQuestions: ', [$params]);
    }
}
