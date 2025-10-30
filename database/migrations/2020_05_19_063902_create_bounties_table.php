<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBountiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bounties', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('game_id');
            $table->unsignedInteger('bounty_claim_request_id')->nullable();
            $table->decimal('price', 30, 10);
            $table->decimal('escrow_balance', 30, 10)->nullable();
            $table->decimal('fee', 30, 10)->nullable();
            $table->string('title');
            $table->text('description');
            $table->text('slug');
            $table->text('media')->nullable();
            $table->string('status');
            $table->unsignedTinyInteger('user_has_review')->default(0);
            $table->unsignedTinyInteger('claimer_has_review')->default(0);
            $table->unsignedInteger('reason_id')->nullable();
            $table->unsignedInteger('rank_id')->nullable();
            $table->unsignedInteger('user_level_meta_id')->nullable();
            $table->unsignedBigInteger('stopped_at')->nullable();
            $table->timestamps();

            $table->softDeletes();

            $table->index('game_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bounties');
    }
}
