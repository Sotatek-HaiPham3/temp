<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChannelMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('channel_members', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('channel_id');
            $table->unsignedInteger('user_id');
            $table->unsignedTinyInteger('is_blocked')->default(0);
            $table->unsignedTinyInteger('is_muted')->default(0);
            $table->timestamps();

            $table->unique(['channel_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('channel_members');
    }
}
