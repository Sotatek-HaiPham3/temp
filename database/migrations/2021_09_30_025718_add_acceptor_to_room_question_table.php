<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAcceptorToRoomQuestionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('room_questions', function (Blueprint $table) {
            $table->unsignedBigInteger('acceptor_id')->nullable()->after('rejector_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('room_questions', function (Blueprint $table) {
            $table->dropColumn('acceptor_id');
        });
    }
}
