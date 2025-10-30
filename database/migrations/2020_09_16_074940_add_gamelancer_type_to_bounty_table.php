<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGamelancerTypeToBountyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bounties', function (Blueprint $table) {
            $table->unsignedTinyInteger('gamelancer_type')->default(1)->after('description');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bounties', function (Blueprint $table) {
            $table->dropColumn('gamelancer_type');
        });
    }
}
