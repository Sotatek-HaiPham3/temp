<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('gamelancer_id');
            $table->unsignedInteger('claimer_id');
            $table->unsignedInteger('game_profile_id');
            $table->unsignedInteger('offer_id');
            $table->unsignedInteger('reason_id')->nullable();
            $table->unsignedInteger('channel_id');
            $table->decimal('quantity', 30, 10);
            $table->decimal('quantity_played', 30, 10)->nullable()->default(0);
            $table->decimal('escrow_balance', 30, 10)->nullable();
            $table->decimal('fee', 30, 10);
            $table->unsignedBigInteger('booked_at');
            $table->unsignedBigInteger('schedule_at');
            $table->unsignedBigInteger('start_at')->nullable();
            $table->unsignedBigInteger('end_at')->nullable();
            $table->unsignedInteger('next_game_user_id')->nullable();
            $table->unsignedTinyInteger('claimer_stop')->nullable()->default(0);
            $table->unsignedTinyInteger('gamelancer_stop')->nullable()->default(0);
            $table->unsignedTinyInteger('claimer_ready')->nullable()->default(0);
            $table->unsignedTinyInteger('gamelancer_ready')->nullable()->default(0);
            $table->unsignedTinyInteger('user_has_review')->default(0);
            $table->unsignedTinyInteger('claimer_has_review')->default(0);
            $table->unsignedTinyInteger('claimer_absent')->nullable()->default(0);
            $table->unsignedTinyInteger('gamelancer_absent')->nullable()->default(0);
            $table->string('status', 20);
            $table->timestamps();

            $table->index(['game_profile_id', 'gamelancer_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sessions');
    }
}
