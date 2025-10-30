<?php

use Illuminate\Database\Seeder;

class UserLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('user_levels_meta')->truncate();

        $levelList = 100;
        for ($i = 1; $i < $levelList + 1; $i++) {
            DB::table('user_levels_meta')->insert([
                'name'        => "Level {$i}",
                'level'       => $i,
                'image'       => \Storage::url('/level.png')
            ]);
        }
    }
}
