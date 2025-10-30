<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableCommunities extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('communities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('mattermost_channel_id')->nullable(false)->unique();
            $table->string('name')->nullable(false);
            $table->string('slug')->unique();
            $table->text('description')->nullable(true);
            $table->text('photo')->nullable(true);
            $table->integer('gallery_id')->nullable(true);
            $table->string('status')->nullable(false)->default('active')->comment('active | deactivated | deleted');
            $table->integer('total_users')->nullable(true)->default(0);
            $table->integer('leader_count')->nullable(true)->default(0);
            $table->integer('member_count')->nullable(true)->default(0);
            $table->integer('total_request')->nullable(true)->default(0);
            $table->integer('total_rooms')->nullable(true)->default(0);
            $table->integer('total_rooms_size')->nullable(true)->default(0);
            $table->integer('total_rooms_user')->nullable(true)->default(0);
            $table->tinyInteger('is_private')->nullable(false)->default(false);
            $table->unsignedBigInteger('creator_id')->nullable(false);
            $table->timestamp('inactive_at')->nullable(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['mattermost_channel_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('communities');
    }
}
