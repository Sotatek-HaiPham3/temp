<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTakenAtToInvitationCodeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invitation_codes', function (Blueprint $table) {
            $table->timestamp('taken_at')->nullable()->after('code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invitation_codes', function (Blueprint $table) {
            $table->dropColumn('taken_at');
        });
    }
}
