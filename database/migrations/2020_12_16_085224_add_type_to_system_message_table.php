<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTypeToSystemMessageTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('session_system_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('sender_id')->nullable()->after('channel_id');
            $table->text('message_type')->nullable()->after('message_props');
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
            $table->dropColumn('sender_id');
            $table->dropColumn('message_type');
        });
    }
}
