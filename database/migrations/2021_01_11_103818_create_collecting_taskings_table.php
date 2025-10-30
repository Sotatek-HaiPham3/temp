<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCollectingTaskingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('collecting_taskings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('tasking_id');
            $table->unsignedBigInteger('quantity')->default(1);
            $table->unsignedBigInteger('collected_at')->nullable();
            $table->timestamps();

            $table->index(['user_id'], 'user_id_index');
            $table->index(['user_id', 'tasking_id'], 'user_id_tasking_id_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('collecting_taskings');
    }
}
