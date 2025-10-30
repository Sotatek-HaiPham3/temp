<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableCommunityMembers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('community_members', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('community_id')->nullable(false);
            $table->unsignedBigInteger('user_id')->nullable(false);
            $table->unsignedBigInteger('viewed_at')->nullable(true);
            $table->unsignedBigInteger('invited_user_id')->nullable(true);
            $table->string('role')->nullable(false)->comment('owner, leader, member');
            $table->unsignedBigInteger('kicked_by')->nullable(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['community_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('community_members');
    }
}
