<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGameStatisticsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_statistics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('game_id')->unique()->index();
            $table->text('user_ids');
            $table->integer('total_sessions')->nullable();
            $table->decimal('total_coins', 30, 10)->nullable();
            $table->decimal('total_bars', 30, 10)->nullable();
            $table->decimal('total_quantity_per_game', 30, 10)->nullable();
            $table->decimal('total_quantity_per_hour', 30, 10)->nullable();
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
        Schema::dropIfExists('game_statistics');
    }
}
