<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSubmittedAtToReviewTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('session_reviews', function (Blueprint $table) {
            $table->unsignedBigInteger('submit_at')->after('recommend');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('session_reviews', function (Blueprint $table) {
            $table->dropColumn('submit_at');
        });
    }
}
