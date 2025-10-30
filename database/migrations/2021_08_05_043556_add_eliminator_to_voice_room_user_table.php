<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEliminatorToVoiceRoomUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('voice_chat_room_users', function (Blueprint $table) {
            $table->unsignedBigInteger('eliminator_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('voice_chat_room_users', function (Blueprint $table) {
            $table->dropColumn('eliminator_id');
        });
    }
}
