<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableChangeDataTypesToGamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('games', function (Blueprint $table) {
            $table->text('logo')->nullable(true)->change();
            $table->text('thumbnail')->nullable(true)->change();
            $table->text('portrait')->nullable(true)->change();
            $table->text('cover')->nullable(true)->change();
            $table->text('banner')->nullable(true)->change();
            $table->text('thumbnail_active')->nullable(true)->change();
            $table->text('thumbnail_hover')->nullable(true)->change();
            $table->text('portrait_background')->nullable(true)->change();
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
            $table->text('logo')->nullable(false)->change();
            $table->text('thumbnail')->nullable(false)->change();
            $table->text('portrait')->nullable(false)->change();
            $table->text('cover')->nullable(false)->change();
            $table->text('banner')->nullable(false)->change();
            $table->text('thumbnail_active')->nullable(false)->change();
            $table->text('thumbnail_hover')->nullable(false)->change();
            $table->text('portrait_background')->nullable(false)->change();
        });
    }
}
