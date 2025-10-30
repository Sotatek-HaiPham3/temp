<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRoomInvitationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('room_invitations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('room_id');
            $table->unsignedInteger('sender_id');
            $table->unsignedInteger('receiver_id');
            $table->string('type');
            $table->string('status');
            $table->text('description')->nullable();
            $table->softDeletes();
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
        Schema::dropIfExists('room_invitations');
    }
}
