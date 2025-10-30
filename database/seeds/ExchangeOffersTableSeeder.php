<?php

use Illuminate\Database\Seeder;
use App\Utils;

class ExchangeOffersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $assetsUrl = Utils::getSchemeAndHttpHostForAssets();

        DB::table('exchange_offers')->truncate();
        DB::table('exchange_offers')->insert([
            [
                'id'            => 1,
                'bars'          => 100,
                'coins'         => 70,
                'bonus'         => 0,
                'cover'         => "{$assetsUrl}/images/coins/200_coins.svg",
            ],
            [
                'id'            => 2,
                'bars'          => 200,
                'coins'         => 140,
                'bonus'         => 0,
                'cover'         => "{$assetsUrl}/images/coins/500_coins.svg",
            ],
            [
                'id'            => 3,
                'bars'          => 500,
                'coins'         => 350,
                'bonus'         => 0,
                'cover'         => "{$assetsUrl}/images/coins/1000_coins.svg",
            ],
            [
                'id'            => 4,
                'bars'          => 1000,
                'coins'         => 700,
                'bonus'         => 0,
                'cover'         => "{$assetsUrl}/images/coins/3000_coins.svg",
            ],
            [
                'id'            => 5,
                'bars'          => 3000,
                'coins'         => 2100,
                'bonus'         => 0,
                'cover'         => "{$assetsUrl}/images/coins/5000_coins.svg",
            ],
            [
                'id'            => 6,
                'bars'          => 10000,
                'coins'         => 7000,
                'bonus'         => 0,
                'cover'         => "{$assetsUrl}/images/coins/10000_coins.svg",
            ],
        ]);
    }
}
