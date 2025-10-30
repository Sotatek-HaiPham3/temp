<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Services\AdminService;

class RunOrderingGame extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'game:order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run Ordering Game';
    protected $adminService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->adminService = new AdminService();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->adminService->orderGames();
    }
}
