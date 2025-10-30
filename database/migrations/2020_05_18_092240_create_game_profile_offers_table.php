<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Consts;

class CreateGameProfileOffersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_profile_offers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('game_profile_id');
            $table->string('type', 20)->default(Consts::GAME_TYPE_HOUR);
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('price');
            $table->timestamps();

            $table->softDeletes();

            $table->index('game_profile_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_profile_offers');
    }
}
