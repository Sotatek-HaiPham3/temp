<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSmsSettingsToUserSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('session_phone_number')->default(1)->after('session_email');
            $table->unsignedTinyInteger('bounty_phone_number')->default(1)->after('session_email');
            $table->unsignedTinyInteger('marketing_phone_number')->default(1)->after('session_email');
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
            $table->dropColumn('marketing_phone_number');
            $table->dropColumn('bounty_phone_number');
            $table->dropColumn('session_phone_number');
        });
    }
}
