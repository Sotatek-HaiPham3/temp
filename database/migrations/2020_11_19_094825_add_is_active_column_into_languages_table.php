<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsActiveColumnIntoLanguagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('languages', function (Blueprint $table) {
            $table->unsignedTinyInteger('is_active')->default(0)->after('native_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('languages', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
}
