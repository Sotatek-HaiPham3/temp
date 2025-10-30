<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserAndroidPurchasedTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_android_purchased', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id');
            $table->string('package_name');
            $table->string('product_id');
            $table->string('purchase_token');
            $table->integer('quantity')->default(1);
            $table->text('developer_payload')->nullable();
            $table->bigInteger('purchase_time_millis');
            $table->timestamps();

            $table->index(['package_name', 'product_id', 'purchase_token'], 'android_purchase_transaction_index');
            $table->unique(['package_name', 'product_id', 'purchase_token'], 'android_purchase_transaction_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_android_purchased');
    }
}
