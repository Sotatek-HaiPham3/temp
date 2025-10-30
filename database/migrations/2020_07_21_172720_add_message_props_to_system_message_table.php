<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMessagePropsToSystemMessageTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('session_system_messages', function (Blueprint $table) {
            $table->text('message_props')->after('message_key');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('session_system_messages', function (Blueprint $table) {
            $table->dropColumn('message_props');
        });
    }
}
