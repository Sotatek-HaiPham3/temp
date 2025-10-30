<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSettingUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_settings', function (Blueprint $table) {
            $table->text('cover')->nullable()->after('only_online_booking');
            $table->unsignedTinyInteger('online')->default(1)->after('only_online_booking');
            $table->unsignedTinyInteger('visible_age')->default(0)->after('only_online_booking');
            $table->unsignedTinyInteger('visible_gender')->default(0)->after('only_online_booking');
            $table->unsignedTinyInteger('visible_following')->default(0)->after('only_online_booking');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_settings', function (Blueprint $table) {
            $table->dropColumn('cover');
            $table->dropColumn('online');
            $table->dropColumn('visible_age');
        });
    }
}
