<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIosAppIdAndroidAppIdInGamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('games', function (Blueprint $table) {
            $table->string('ios_app_id')->nullable(true)->after('thumbnail_hover');
            $table->string('android_app_id')->nullable(true)->after('ios_app_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn(['ios_app_id', 'android_app_id']);
        });
    }
}
