<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Consts;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->unsignedTinyInteger('email_verified')->default(0);
            $table->string('email_verification_code')->nullable();
            $table->timestamp('email_verification_code_created_at')->nullable();
            $table->unsignedTinyInteger('level')->default(0);
            $table->unsignedTinyInteger('is_gamelancer')->default(0);
            $table->unsignedTinyInteger('is_vip')->nullable()->default(0);
            $table->string('username')->unique()->index();
            $table->string('full_name')->nullable();
            $table->date('dob')->nullable()->comment("date of birth");
            $table->unsignedTinyInteger('sex')->comment('sex of user, 1:male, 0:female]');
            $table->string('status', 20)->default('inactive'); // inactive or active
            $table->text('avatar')->nullable();
            $table->decimal('rating', 10, 5)->default(0);
            $table->bigInteger('number_reviewer')->default(0);
            $table->bigInteger('total_follower')->default(0);
            $table->bigInteger('response_time')->nullable();
            $table->bigInteger('sessions_played')->default(0);
            $table->text('description')->nullable();
            $table->string('languages')->nullable();
            $table->decimal('price', 30, 10)->nullable();
            $table->text('audio')->nullable();
            $table->string('timezone', 50)->default(Consts::USER_DEFAULT_TIMEZONE);
            $table->timestamp('last_time_active')->nullable();
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
