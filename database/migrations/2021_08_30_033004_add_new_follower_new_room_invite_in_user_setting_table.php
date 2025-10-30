<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewFollowerNewRoomInviteInUserSettingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('follower_notification')->default(1)->after('online');
            $table->unsignedTinyInteger('room_invite_notification')->default(1)->after('follower_notification');
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
            $table->dropColumn(['follower_notification', 'room_invite_notification']);
        });
    }
}
