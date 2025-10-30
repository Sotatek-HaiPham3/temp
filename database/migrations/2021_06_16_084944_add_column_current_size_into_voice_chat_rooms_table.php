<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnCurrentSizeIntoVoiceChatRoomsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('voice_chat_rooms', function (Blueprint $table) {
            $table->integer('current_size')->default(0)->after('size');
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
            $table->dropColumn('current_size');
        });
    }
}
