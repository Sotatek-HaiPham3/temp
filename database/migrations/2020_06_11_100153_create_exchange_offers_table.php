<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExchangeOffersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exchange_offers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('coins');
            $table->string('cover');
            $table->decimal('bars', 30, 10)->default(0);
            $table->decimal('bonus', 30, 10)->default(0);
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
        Schema::dropIfExists('exchange_offers');
    }
}
