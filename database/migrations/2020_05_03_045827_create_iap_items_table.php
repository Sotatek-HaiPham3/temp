<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIapItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('iap_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('product_id');
            $table->string('name');
            $table->string('platform');
            $table->string('description')->nullable();
            $table->decimal('price', 30, 10);
            $table->decimal('coin', 30, 10);
            $table->text('cover');
            $table->unsignedTinyInteger('is_actived')->default(1);
            $table->timestamps();

            $table->unique(['product_id', 'platform'], 'iap_id_platform_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('iap_items');
    }
}
