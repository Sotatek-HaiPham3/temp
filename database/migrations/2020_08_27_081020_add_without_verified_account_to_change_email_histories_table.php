<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWithoutVerifiedAccountToChangeEmailHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('change_email_histories', function (Blueprint $table) {
            $table->integer('without_verified_account')->default(0)->after('email_verification_code_created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('change_email_histories', function (Blueprint $table) {
            $table->dropColumn('without_verified_account');
        });
    }
}
