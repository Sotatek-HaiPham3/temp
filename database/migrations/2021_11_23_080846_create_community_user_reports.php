<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCommunityUserReports extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('community_user_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('community_id');
            $table->unsignedInteger('reported_user_id');
            $table->unsignedInteger('reporter_id');
            $table->unsignedInteger('reason_id');
            $table->text('details')->nullable();
            $table->string('status');
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
        Schema::dropIfExists('community_user_reports');
    }
}
