<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveTitleGameprofileTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game_profiles', function (Blueprint $table) {
            $table->dropColumn('title');
            $table->dropColumn('description');
            $table->dropColumn('slug');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game_profiles', function (Blueprint $table) {
            //
        });
    }
}
