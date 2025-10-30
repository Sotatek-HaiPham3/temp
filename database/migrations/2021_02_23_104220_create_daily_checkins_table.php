<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDailyCheckinsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('daily_checkins', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('milestone');
            $table->integer('day');
            $table->string('exp');
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index(['user_id', 'milestone'], 'user_id_milestone_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('daily_checkins');
    }
}
