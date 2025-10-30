<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGameProfileMediasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_profile_medias', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('game_profile_id');
            $table->text('url');
            $table->string('type', 20);
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
        Schema::dropIfExists('game_profile_medias');
    }
}
