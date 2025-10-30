<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserStatisticTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_statistics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id')->unique()->index();
            $table->decimal('rating', 30, 10)->default(0);
            $table->unsignedInteger('total_reviewers')->default(0);
            $table->decimal('session_rating', 30, 10)->default(0);
            $table->unsignedInteger('session_reviewers')->default(0);
            $table->unsignedInteger('total_followers')->default(0);
            $table->unsignedInteger('total_following')->default(0);
            $table->unsignedInteger('response_time')->nullable();
            $table->unsignedInteger('session_played')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_statistics');
    }
}
