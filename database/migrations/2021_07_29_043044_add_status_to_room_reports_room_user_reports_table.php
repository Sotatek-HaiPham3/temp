<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddStatusToRoomReportsRoomUserReportsTable extends Migration
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
            Schema::table('room_reports', function (Blueprint $table) {
                $table->string('status')->nullable(false)->default(\App\Consts::REPORT_STATUS_PROCESSING)->after('details');
            });

            Schema::table('room_user_reports', function (Blueprint $table) {
                $table->string('status')->nullable(false)->default(\App\Consts::REPORT_STATUS_PROCESSING)->after('details');
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
            Schema::table('room_reports', function (Blueprint $table) {
                $table->dropColumn('status');
            });

            Schema::table('room_user_reports', function (Blueprint $table) {
                $table->dropColumn('status');
            });
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
