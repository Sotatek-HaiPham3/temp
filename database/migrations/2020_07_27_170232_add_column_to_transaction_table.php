<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnToTransactionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->text('message_key')->after('memo')->nullable();
            $table->text('message_props')->after('memo')->nullable();
            $table->text('internal_type')->after('memo')->nullable();
            $table->text('internal_type_id')->after('memo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('message_key');
            $table->dropColumn('message_props');
            $table->dropColumn('internal_type');
            $table->dropColumn('internal_type_id');
        });
    }
}
