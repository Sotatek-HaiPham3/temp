<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAllowShareScreenToCommunitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('communities', function (Blueprint $table) {
            $table->tinyInteger('allow_share_screen')->nullable(true)->default(1)->after('inactive_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('communities', function (Blueprint $table) {
            $table->dropColumn('allow_share_screen');
        });
    }
}
