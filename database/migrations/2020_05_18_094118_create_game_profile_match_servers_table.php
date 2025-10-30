<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGameProfileMatchServersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_profile_match_servers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('game_profile_id');
            $table->unsignedInteger('game_server_id');
            $table->timestamps();

            $table->index('game_profile_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_profile_match_servers');
    }
}
