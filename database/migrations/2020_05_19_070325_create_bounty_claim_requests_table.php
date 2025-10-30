<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBountyClaimRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bounty_claim_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('bounty_id');
            $table->unsignedInteger('gamelancer_id');
            $table->unsignedInteger('channel_id');
            $table->unsignedInteger('reason_id')->nullable();
            $table->text('description');
            $table->string('status', 20);
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
        Schema::dropIfExists('bounty_claim_requests');
    }
}
