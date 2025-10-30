<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVoiceChatRoomUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('voice_chat_room_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('room_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('invited_user_id')->nullable();
            $table->unsignedTinyInteger('is_kicked')->default(0);
            $table->string('type');
            $table->string('sid')->nullable();
            $table->string('username')->nullable();
            $table->timestamp('started_time')->nullable();
            $table->timestamp('ended_time')->nullable();
            $table->timestamps();

            $table->index('room_id');
            $table->index(['room_id', 'user_id', 'sid']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('voice_chat_room_users');
    }
}
