<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedTinyInteger('message_email')->default(1);
            $table->unsignedTinyInteger('favourite_email')->default(1);
            $table->unsignedTinyInteger('marketing_email')->default(1);
            $table->unsignedTinyInteger('bounty_email')->default(1);
            $table->unsignedTinyInteger('session_email')->default(1);
            $table->unsignedTinyInteger('public_chat')->default(1);
            $table->unsignedTinyInteger('user_has_money_chat')->default(0);
            $table->unsignedTinyInteger('auto_accept_booking')->default(1);
            $table->unsignedTinyInteger('only_online_booking')->default(1);
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
        Schema::dropIfExists('user_settings');
    }
}
