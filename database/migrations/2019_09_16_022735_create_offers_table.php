<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOffersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('coin');
            $table->string('cover');
            $table->string('stripe_cover')->comment('Image claim coin for stripe');
            $table->decimal('price', 30, 10)->default(0);
            $table->decimal('bonus', 30, 10)->default(0);
            $table->boolean('always_bonus')->default(1)->comment('Sepcify bonus first time purchase');
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
        Schema::dropIfExists('offers');
    }
}
