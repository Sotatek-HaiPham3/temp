<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTaskingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('taskings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type');
            $table->string('title');
            $table->string('code');
            $table->text('description')->nullable();
            $table->text('short_title')->nullable();
            $table->text('short_description')->nullable();
            $table->decimal('exp', 30, 10)->default(0);
            $table->decimal('threshold_exp_in_day', 30, 10)->nullable();
            $table->decimal('bonus_value', 30, 10)->nullable();
            $table->string('bonus_currency')->nullable();
            $table->text('url')->nullable();
            $table->unsignedInteger('order')->comment('Prioritize of Task');
            $table->timestamps();
            $table->softDeletes();

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
        Schema::dropIfExists('taskings');
    }
}
