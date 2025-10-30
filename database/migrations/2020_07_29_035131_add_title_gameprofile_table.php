<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTitleGameprofileTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game_profiles', function (Blueprint $table) {
            $table->unsignedInteger('rank_id')->nullable()->change();
            $table->string('title')->nullable()->after('rank_id');
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
