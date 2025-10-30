<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCheckinMilestoneToUserRankingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_rankings', function (Blueprint $table) {
            $table->timestamp('checkin_milestone')->nullable()->after('intro_step');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_rankings', function (Blueprint $table) {
            $table->dropColumn('checkin_milestone');
        });
    }
}
