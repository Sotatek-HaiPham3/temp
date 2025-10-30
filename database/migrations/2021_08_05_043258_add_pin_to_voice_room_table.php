<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPinToVoiceRoomTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('voice_chat_rooms', function (Blueprint $table) {
            $table->unsignedBigInteger('creator_id');
            $table->unsignedInteger('pinned');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('voice_chat_rooms', function (Blueprint $table) {
            $table->dropColumn(['creator_id', 'pinned']);
        });
    }
}
