<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPinnedToRoomCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('room_categories', function (Blueprint $table) {
            $table->tinyInteger('pinned')->default(0)->after('is_public');
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
            $table->dropColumn('pinned');
        });
    }
}
