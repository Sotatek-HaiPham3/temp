<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSessionSystemMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('session_system_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('channel_id');
            $table->unsignedInteger('object_id');
            $table->string('object_type', 30);
            $table->text('message_key');
            $table->text('data');
            $table->unsignedInteger('is_processed');
            $table->unsignedBigInteger('started_event');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('session_system_messages');
    }
}
