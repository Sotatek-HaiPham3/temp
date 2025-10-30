<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserInterestsGameMatchServersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_interests_game_match_servers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_interests_game_id');
            $table->unsignedInteger('game_server_id');
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
        Schema::dropIfExists('user_interests_game_match_servers');
    }
}
