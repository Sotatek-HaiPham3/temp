<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChangePhoneNumberHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('change_phone_number_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id');
            $table->string('old_phone_number', 20);
            $table->string('new_phone_number', 20);
            $table->string('new_phone_country_code', 5);
            $table->string('verification_code')->nullable();
            $table->unsignedTinyInteger('verified')->default(0);
            $table->timestamp('verification_code_created_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('change_phone_number_histories');
    }
}
