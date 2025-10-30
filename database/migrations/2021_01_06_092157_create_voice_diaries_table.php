<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVoiceDiariesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('voice_diaries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('channel_id');
            $table->string('hash');
            $table->unsignedBigInteger('caller_id');
            $table->unsignedBigInteger('receiver_id');
            $table->string('status');
            $table->timestamp('started_time')->nullable();
            $table->timestamp('ended_time')->nullable();
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
        Schema::dropIfExists('voice_diaries');
    }
}
