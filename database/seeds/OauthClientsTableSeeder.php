<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OauthClientsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('oauth_clients')->truncate();
        DB::table('oauth_personal_access_clients')->truncate();

        $clientId = DB::table('oauth_clients')->insertGetId([
            'name' => env('APP_NAME').' Password Grant Client',
            'secret' => env('CLIENT_SECRET'),
            'redirect' => 'http://localhost',
            'personal_access_client' => 1,
            'password_client' => 1,
            'revoked' => 0,
            'created_at' => \Carbon\Carbon::now(),
            'updated_at' => \Carbon\Carbon::now()
        ]);

        DB::table('oauth_personal_access_clients')->insert([
            'client_id' => $clientId,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
    }
}
