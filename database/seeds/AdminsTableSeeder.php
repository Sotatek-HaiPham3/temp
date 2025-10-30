<?php

use Illuminate\Database\Seeder;
use App\Consts;
use Carbon\Carbon;

class AdminsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('admins')->truncate();
        DB::table('admins')->insert([
            [
                'id'            => 1,
                'name'          => 'Gamelancer Admin',
                'email'         => 'admin@gamelancer.com',
                'password'      => bcrypt('123123'),
                'role'          => Consts::ROLE_SUPER_ADMIN,
                'created_at'    => Carbon::now()
            ]
        ]);
    }
}
