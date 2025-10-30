<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSystemNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('system_notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('receiver_id')->index();
            $table->string('type');
            $table->text('message');
            $table->text('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['receiver_id', 'type'], 'receiver_id_type_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('system_notifications');
    }
}
