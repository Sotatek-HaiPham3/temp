<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableCommunityRequests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('community_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable(false);
            $table->unsignedBigInteger('community_id')->nullable(false);
            $table->string('message')->nullable(true);
            $table->string('status')->nullable(false)->comment('created, accepted, cancel');
            $table->timestamps();

            $table->index(['community_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('community_requests');
    }
}
