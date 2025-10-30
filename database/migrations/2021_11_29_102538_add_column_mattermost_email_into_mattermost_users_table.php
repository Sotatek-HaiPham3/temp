<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnMattermostEmailIntoMattermostUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mattermost_users', function (Blueprint $table) {
            $table->string('mattermost_email')->after('mattermost_user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mattermost_users', function (Blueprint $table) {
            $table->dropColumn('mattermost_email');
        });
    }
}
