<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGameRanksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_ranks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('game_id');
            $table->string('name');
            $table->timestamps();

            $table->index('game_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_ranks');
    }
}
