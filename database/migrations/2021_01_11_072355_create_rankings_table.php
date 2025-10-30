<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRankingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rankings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code');
            $table->string('name');
            $table->decimal('exp', 30, 10)->default(0);
            $table->decimal('threshold_exp_in_day', 30, 10)->default(0);
            $table->text('url')->nullable();
            $table->unsignedInteger('order')->comment('Prioritize of Rank');
            $table->timestamps();
            $table->softDeletes();

            $table->index('code', 'rank_code_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rankings');
    }
}
