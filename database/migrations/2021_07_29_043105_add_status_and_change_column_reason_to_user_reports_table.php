<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddStatusAndChangeColumnReasonToUserReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::beginTransaction();
        try {
            Schema::table('user_reports', function (Blueprint $table) {
                $table->integer('reason_id')->nullable(false)->after('report_user_id');
                $table->string('status')->nullable(false)->default(\App\Consts::REPORT_STATUS_PROCESSING)->after('report_user_id');

                // change from not null to nullable
                $table->text('reason')->nullable(true)->change();
            });

            Schema::table('user_reports', function (Blueprint $table) {
                $table->renameColumn('reason', 'details');
            });
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::beginTransaction();
        try {
            Schema::table('user_reports', function (Blueprint $table) {
                $table->dropColumn(['reason_id', 'status']);
                $table->renameColumn('details', 'reason');
            });

            Schema::table('user_reports', function (Blueprint $table) {
                // change from nullable to not null
                $table->text('reason')->nullable(false)->change();
            });
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
