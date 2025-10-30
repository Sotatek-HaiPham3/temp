<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTaskingRewardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasking_rewards', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type');
            $table->string('level');
            $table->decimal('quantity', 30, 10)->default(0);
            $table->string('currency');
            $table->timestamps();

            $table->index('type', 'type_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tasking_rewards');
    }
}
