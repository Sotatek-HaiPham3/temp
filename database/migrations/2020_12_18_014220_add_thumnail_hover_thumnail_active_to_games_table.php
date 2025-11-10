<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddThumnailHoverThumnailActiveToGamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('games', function (Blueprint $table) {
            $table->text('thumbnail_hover')->after('cover')->nullable();
            $table->text('thumbnail_active')->after('cover')->nullable();
            $table->text('banner')->after('cover')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('thumbnail_hover');
            $table->dropColumn('thumbnail_active');
            $table->dropColumn('banner');
        });
    }
}
