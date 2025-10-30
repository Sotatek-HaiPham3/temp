<?php

use Illuminate\Database\Seeder;
use App\Consts;
use Carbon\Carbon;

class ReasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('reasons')->truncate();
        DB::table('reasons')->insert([
            [
                'object_type'       => Consts::OBJECT_TYPE_SESSION,
                'reason_type'       => Consts::REASON_TYPE_CANCEL,
                'content'           => 'I don\'t have time.',
                'static_reason'     => Consts::TRUE
            ],
            [
                'object_type'       => Consts::OBJECT_TYPE_SESSION,
                'reason_type'       => Consts::REASON_TYPE_CANCEL,
                'content'           => 'Problems with my setup.',
                'static_reason'     => Consts::TRUE
            ],
            [
                'object_type'       => Consts::OBJECT_TYPE_SESSION,
                'reason_type'       => Consts::REASON_TYPE_CANCEL,
                'content'           => 'I had an emergency.',
                'static_reason'     => Consts::TRUE
            ],
            [
                'object_type'       => Consts::OBJECT_TYPE_SESSION,
                'reason_type'       => Consts::REASON_TYPE_STOP,
                'content'           => 'Something came up.',
                'static_reason'     => Consts::TRUE
            ],
            [
                'object_type'       => Consts::OBJECT_TYPE_SESSION,
                'reason_type'       => Consts::REASON_TYPE_STOP,
                'content'           => 'The session is completed.',
                'static_reason'     => Consts::TRUE
            ],
            [
                'object_type'       => Consts::OBJECT_TYPE_SESSION,
                'reason_type'       => Consts::REASON_TYPE_STOP,
                'content'           => 'I had an issue with the player.',
                'static_reason'     => Consts::TRUE
            ],
            [
                'object_type'       => Consts::OBJECT_TYPE_BOUNTY,
                'reason_type'       => Consts::REASON_TYPE_CANCEL,
                'content'           => 'I don\'t have time.',
                'static_reason'     => Consts::TRUE
            ],
            [
                'object_type'       => Consts::OBJECT_TYPE_BOUNTY,
                'reason_type'       => Consts::REASON_TYPE_CANCEL,
                'content'           => 'This is too hard.',
                'static_reason'     => Consts::TRUE
            ],
            [
                'object_type'       => Consts::OBJECT_TYPE_BOUNTY,
                'reason_type'       => Consts::REASON_TYPE_CANCEL,
                'content'           => 'I had an emergency.',
                'static_reason'     => Consts::TRUE
            ]
        ]);
    }
}
