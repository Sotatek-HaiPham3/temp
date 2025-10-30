<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCoinPriceSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coin_price_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('region');
            $table->string('bar_to_usd')->nullable();
            $table->string('bar_to_coin')->nullable();
            $table->string('coin_to_bar')->nullable();
            $table->string('coin_to_usd')->nullable();
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
        Schema::dropIfExists('coin_price_settings');
    }
}
