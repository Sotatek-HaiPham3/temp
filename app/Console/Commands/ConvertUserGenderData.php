<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Utils;
use App\Consts;
use DB;
use Exception;

class ConvertUserGenderData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gender-user:convert';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert User Gender Data';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (Utils::isProduction() && !$this->confirm('Do you wish to continue?')) {
            return;
        }

        DB::beginTransaction();
        try {
            User::where('sex', '0')->update(['sex' => Consts::GENDER_FEMALE]);
            User::where('sex', '1')->update(['sex' => Consts::GENDER_MALE]);
            User::where('sex', '2')->update(['sex' => Consts::GENDER_NON_BINARY]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
