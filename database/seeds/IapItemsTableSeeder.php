<?php

use Illuminate\Database\Seeder;
use App\Models\IapItem;
use App\Consts;
use App\Utils;

class IapItemsTableSeeder extends Seeder
{

    const IAP_ITEMS = [
        [
            'product_id'    => 'com.gamelancer.pack_199',
            'name'          => 'Pack $1.99',
            'price'         => 1.99,
            'coin'          => 140,
            'cover'         => '/images/coins/200_coins.svg'
        ],
        [
            'product_id'    => 'com.gamelancer.pack_499',
            'name'          => 'Pack $4.99',
            'price'         => 4.99,
            'coin'          => 350,
            'cover'         => '/images/coins/500_coins.svg'
        ],
        [
            'product_id'    => 'com.gamelancer.pack_999',
            'name'          => 'Pack $9.99',
            'price'         => 9.99,
            'coin'          => 700,
            'cover'         => '/images/coins/1000_coins.svg'
        ],
        [
            'product_id'    => 'com.gamelancer.pack_2999',
            'name'          => 'Pack $29.99',
            'price'         => 29.99,
            'coin'          => 2100,
            'cover'         => '/images/coins/3000_coins.svg'
        ],
        [
            'product_id'    => 'com.gamelancer.pack_4999',
            'name'          => 'Pack $49.99',
            'price'         => 49.99,
            'coin'          => 3500,
            'cover'         => '/images/coins/5000_coins.svg'
        ],
        [
            'product_id'    => 'com.gamelancer.pack_9999',
            'name'          => 'Pack $99.99',
            'price'         => 99.99,
            'coin'          => 7000,
            'cover'         => '/images/coins/10000_coins.svg'
        ],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('iap_items')->truncate();

        $this->createIapByPlatform(Consts::IAP_PLATFORM_IOS);
        $this->createIapByPlatform(Consts::IAP_PLATFORM_ANDROID);
    }

    private function createIapByPlatform($platform)
    {
        $assetsUrl = Utils::getSchemeAndHttpHostForAssets();

        foreach (static::IAP_ITEMS as $value) {
            if (! Utils::isProduction()) {
                $value['cover'] = sprintf('%s%s', $assetsUrl, $value['cover']);
            }
            IapItem::create(array_merge($value, [
                'platform' => $platform,
            ]));
        }
    }
}
