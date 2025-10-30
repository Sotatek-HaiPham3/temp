<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableCommunityReports extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('community_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('community_id')->nullable(false);
            $table->unsignedBigInteger('reporter_id')->nullable(false);
            $table->unsignedBigInteger('reason_id')->nullable(false);
            $table->text('details')->nullable(true);
            $table->string('status')->nullable(false)->default('processing')->comment('processing, processed');
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
        Schema::dropIfExists('community_reports');
    }
}
