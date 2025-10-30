<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableCommunityInvitations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('community_invitations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('community_id')->nullable(false);
            $table->unsignedBigInteger('sender_id')->nullable(false);
            $table->unsignedBigInteger('receiver_id')->nullable(false);
            $table->string('status')->nullable(false)->comment('created, accepted');
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
        Schema::dropIfExists('community_invitations');
    }
}
