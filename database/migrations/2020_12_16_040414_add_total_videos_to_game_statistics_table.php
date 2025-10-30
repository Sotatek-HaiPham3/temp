<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTotalVideosToGameStatisticsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game_statistics', function (Blueprint $table) {
            $table->unsignedInteger('total_videos')->nullable()->default(0)->after('user_ids');
            $table->text('user_ids')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game_statistics', function (Blueprint $table) {
            $table->dropColumn('total_videos');
        });
    }
}
