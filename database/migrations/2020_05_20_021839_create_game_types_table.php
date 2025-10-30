<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Consts;

class CreateGameTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('game_id');
            $table->string('type', 20)->default(Consts::GAME_TYPE_HOUR);
            $table->unsignedTinyInteger('is_active')->default(1);
            $table->timestamps();

            $table->index('game_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_types');
    }
}
