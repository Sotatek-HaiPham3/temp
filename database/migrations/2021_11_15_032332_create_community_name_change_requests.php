<?php

use App\Consts;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCommunityNameChangeRequests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('community_name_change_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('community_id')->nullable(false);
            $table->unsignedBigInteger('request_user_id')->nullable(false);
            $table->unsignedBigInteger('reason_id')->nullable(false);
            $table->string('old_name')->nullable(false);
            $table->string('new_name')->nullable(false);
            $table->string('status')->nullable(false)->default(Consts::COMMUNITY_STATUS_PENDING)->comment('pending, canceled, approved, rejected');
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
        Schema::dropIfExists('community_name_change_requests');
    }
}
