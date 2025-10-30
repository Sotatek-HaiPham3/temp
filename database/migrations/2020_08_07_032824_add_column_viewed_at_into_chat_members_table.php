<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnViewedAtIntoChatMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('channel_members', function (Blueprint $table) {
            $table->unsignedBigInteger('viewed_at')->after('is_muted')->nullable();
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
            $table->dropColumn('viewed_at');
        });
    }
}
