<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ReviewTagTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tags = [
            'interactive',
            'sweet voice',
            'fast response',
            'creative',
        ];

        DB::table('review_tags')->truncate();

        foreach ($tags as $value) {
            DB::table('review_tags')->insert([
                'key'           => Str::slug($value, '_'),
                'content'       => $value,
                'created_at'    => now(),
                'updated_at'    => now()
            ]);
        }
    }
}
