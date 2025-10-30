<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableAddColumnCommunityIdToVoiceChatRoomsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('voice_chat_rooms', function (Blueprint $table) {
            $table->unsignedBigInteger('community_id')->nullable('true');
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
            $table->dropColumn('community_id');
        });
    }
}
