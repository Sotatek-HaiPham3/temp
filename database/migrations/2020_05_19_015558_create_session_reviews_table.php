<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSessionReviewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('session_reviews', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('object_id'); // session ID or bounty ID
            $table->unsignedInteger('game_profile_id')->nullable();
            $table->string('object_type');
            $table->unsignedInteger('reviewer_id');
            $table->unsignedInteger('user_id');
            $table->decimal('rate', 30, 10);
            $table->text('description');
            $table->timestamps();

            $table->index(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('session_reviews');
    }
}
