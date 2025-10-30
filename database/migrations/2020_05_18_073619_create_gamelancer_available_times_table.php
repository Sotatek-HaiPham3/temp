<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGamelancerAvailableTimesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gamelancer_available_times', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id');
            $table->unsignedTinyInteger('weekday');
            $table->unsignedInteger('from')->nullable();
            $table->unsignedInteger('to')->nullable();
            $table->unsignedTinyInteger('all')->default(0);
            $table->timestamps();

            $table->index('user_id');
            $table->unique(['user_id', 'weekday', 'from', 'to'], 'unique_available_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gamelancer_available_times');
    }
}
