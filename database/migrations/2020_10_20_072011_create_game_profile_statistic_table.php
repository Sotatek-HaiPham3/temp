<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGameProfileStatisticTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_profile_statistics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('game_profile_id')->unique()->index();
            $table->unsignedInteger('total_played')->default(0);
            $table->unsignedInteger('game_played')->default(0);
            $table->decimal('hour_played', 30, 10)->default(0);
            $table->unsignedInteger('recommend')->default(0);
            $table->unsignedInteger('unrecommend')->default(0);
            $table->decimal('rating')->default(0);
            $table->unsignedInteger('total_review')->default(0);
            $table->timestamp('executed_date')->nullable();
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
        Schema::dropIfExists('game_profile_statistics');
    }
}
