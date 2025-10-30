<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnTotalUsersAndTotalRoomsIntoRoomCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('room_categories', function (Blueprint $table) {
            $table->integer('total_user')->default(0)->after('size_range');
            $table->integer('total_room')->default(0)->after('total_user');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('room_categories', function (Blueprint $table) {
            $table->dropColumn('total_user');
            $table->dropColumn('total_room');
        });
    }
}
