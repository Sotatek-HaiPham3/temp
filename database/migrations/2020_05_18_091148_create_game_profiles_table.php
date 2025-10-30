<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGameProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_profiles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('game_id');
            $table->unsignedInteger('rank_id');
            $table->string('title');
            $table->text('description');
            $table->text('slug');
            $table->text('audio')->nullable();
            $table->unsignedTinyInteger('is_active')->default(1);
            $table->timestamps();

            $table->softDeletes();

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
        Schema::dropIfExists('game_profiles');
    }
}
