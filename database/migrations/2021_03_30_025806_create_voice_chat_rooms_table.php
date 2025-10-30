<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVoiceChatRoomsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('voice_chat_rooms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id');
            $table->integer('game_id');
            $table->unsignedTinyInteger('is_private')->default(0);
            $table->string('type');
            $table->string('name')->index()->unique();
            $table->string('title')->nullable();
            $table->string('topic')->nullable();
            $table->integer('topic_id')->nullable();
            $table->integer('size');
            $table->string('region')->nullable();
            $table->string('code')->nullable();
            $table->string('rules')->nullable();
            $table->string('background_url')->nullable();
            $table->string('status');
            $table->timestamps();

            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('voice_chat_rooms');
    }
}
