<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id');
            $table->string('transaction_id');
            $table->string('paypal_token')->nullable()->comment('The token that user deposit in site use paypal');
            $table->decimal('real_amount', 30, 10)->nullable()->comment('The amount that user deposit or withdrew outsite');
            $table->string('real_currency', 20)->nullable()->comment('The currency that is sent or withdrew outside.');
            $table->decimal('amount', 30, 10)->nullable();
            $table->string('currency', 20)->nullable();
            $table->string('payment_type', 20);
            $table->string('type', 20);
            $table->string('status');
            $table->string('memo')->nullable();
            $table->integer('without_logged')->default(0)->nullable();
            $table->text('error_detail')->nullable();
            $table->unsignedInteger('offer_id')->nullable();
            $table->string('paypal_receiver_email')->nullable();
            $table->bigInteger('created_at');
            $table->bigInteger('updated_at');

            $table->index('user_id');
            $table->index(['user_id', 'status'], 'user_id_status_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
