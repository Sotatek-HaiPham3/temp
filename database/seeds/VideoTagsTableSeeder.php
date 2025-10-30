<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VideoTagsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tags = [
            'gameplay',
            'stream',
            'funny',
        ];

        DB::table('video_tags')->truncate();

        foreach ($tags as $value) {
            DB::table('video_tags')->insert([
                'key'           => Str::slug($value, '_'),
                'content'       => $value,
                'created_at'    => now(),
                'updated_at'    => now()
            ]);
        }
    }
}
