<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnIsSentMessageIntoChannelMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('channel_members', function (Blueprint $table) {
            $table->unsignedTinyInteger('is_sent_message')->default(0)->after('is_muted');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('channel_members', function (Blueprint $table) {
            $table->dropColumn('is_sent_message');
        });
    }
}
