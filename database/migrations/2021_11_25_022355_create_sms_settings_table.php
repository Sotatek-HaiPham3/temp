<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSmsSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sms_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->decimal('max_price', 5, 5);
            $table->decimal('rate_limit_price', 5, 5);
            $table->integer('rate_limit_ttl');
            $table->integer('rate_limit');
            $table->string('white_list')->nullable();
            $table->string('rate_list')->nullable();
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
        Schema::dropIfExists('sms_settings');
    }
}
