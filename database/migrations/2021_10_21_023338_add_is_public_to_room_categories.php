<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsPublicToRoomCategories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('room_categories', function (Blueprint $table) {
            $table->tinyInteger('is_public')->nullable(false)->default(true)->after('description');
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
            $table->dropColumn('is_public');
        });
    }
}
