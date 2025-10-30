<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserDeviceRegistersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_device_registers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('kind');
            $table->string('name');
            $table->string('operating_system')->nullable();
            $table->string('platform')->nullable();
            $table->enum('state', array('connectable', 'blocked'))->default('connectable');
            $table->string('user_device_identify');
            $table->string('latest_ip_address')->nullable();
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
        Schema::dropIfExists('user_device_registers');
    }
}
