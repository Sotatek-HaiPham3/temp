<?php

use Illuminate\Database\Seeder;
use App\Utils;

class OffersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $assetsUrl = Utils::getSchemeAndHttpHostForAssets();

        DB::table('offers')->truncate();
        DB::table('offers')->insert([
            [
                'id'            => 1,
                'bonus'         => 0,
                'price'         => 1.99,
                'coin'          => 200,
                'cover'         => "{$assetsUrl}/images/coins/200_coins.svg",
                'stripe_cover'  => "{$assetsUrl}/images/coins/stripe_200_coins.svg"
            ],
            [
                'id'            => 2,
                'bonus'         => 0,
                'price'         => 4.99,
                'coin'          => 500,
                'cover'         => "{$assetsUrl}/images/coins/500_coins.svg",
                'stripe_cover'  => "{$assetsUrl}/images/coins/stripe_500_coins.svg"
            ],
            [
                'id'            => 3,
                'bonus'         => 0,
                'price'         => 9.99,
                'coin'          => 1000,
                'cover'         => "{$assetsUrl}/images/coins/1000_coins.svg",
                'stripe_cover'  => "{$assetsUrl}/images/coins/stripe_1000_coins.svg"
            ],
            [
                'id'            => 4,
                'bonus'         => 0,
                'price'         => 29.99,
                'coin'          => 3000,
                'cover'         => "{$assetsUrl}/images/coins/3000_coins.svg",
                'stripe_cover'  => "{$assetsUrl}/images/coins/stripe_3000_coins.svg"
            ],
            [
                'id'            => 5,
                'bonus'         => 0,
                'price'         => 49.99,
                'coin'          => 5000,
                'cover'         => "{$assetsUrl}/images/coins/5000_coins.svg",
                'stripe_cover'  => "{$assetsUrl}/images/coins/stripe_5000_coins.svg"
            ],
            [
                'id'            => 6,
                'bonus'         => 0,
                'price'         => 99.99,
                'coin'          => 10000,
                'cover'         => "{$assetsUrl}/images/coins/10000_coins.svg",
                'stripe_cover'  => "{$assetsUrl}/images/coins/stripe_10000_coins.svg"
            ],
        ]);
    }
}
