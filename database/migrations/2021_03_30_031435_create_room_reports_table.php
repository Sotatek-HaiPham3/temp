<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRoomReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('room_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('room_id');
            $table->unsignedInteger('reporter_id');
            $table->unsignedInteger('reason_id');
            $table->text('details')->nullable();
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
        Schema::dropIfExists('room_reports');
    }
}
