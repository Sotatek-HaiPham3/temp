<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPhoneNumberToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone_number', 20)->nullable()->after('email_verification_code_created_at');
            $table->string('phone_country_code', 5)->nullable()->after('phone_number');
            $table->datetime('phone_verify_created_at')->nullable()->after('phone_country_code');
            $table->string('phone_verify_code', 6)->nullable()->after('phone_verify_created_at');
            $table->boolean('phone_verified')->default(0)->after('phone_verify_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone_number', 'phone_country_code', 'phone_verify_created_at', 'phone_verify_code', 'phone_verified');
        });
    }
}
