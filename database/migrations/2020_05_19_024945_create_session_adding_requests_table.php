<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSessionAddingRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('session_adding_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('session_id');
            $table->decimal('quantity', 30, 10);
            $table->string('status', 20);
            $table->decimal('escrow_balance', 30, 10)->nullable();
            $table->timestamps();

            $table->index('session_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('session_adding_requests');
    }
}
