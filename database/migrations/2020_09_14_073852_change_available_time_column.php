<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeAvailableTimeColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('gamelancer_available_times', function (Blueprint $table) {
            $table->unsignedInteger('weekday')->nullable()->change();
            $table->unsignedInteger('all')->nullable()->change();
            $table->unsignedBigInteger('from')->change();
            $table->unsignedBigInteger('to')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('gamelancer_available_times', function (Blueprint $table) {
            //
        });
    }
}
