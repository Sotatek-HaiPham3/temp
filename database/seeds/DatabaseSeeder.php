<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(OauthClientsTableSeeder::class);
        $this->call(LanguagesTableSeeder::class);
        $this->call(CoinPriceSettingsTableSeeder::class);
        $this->call(GamesTableSeeder::class);
        $this->call(OffersTableSeeder::class);
        $this->call(IapItemsTableSeeder::class);
        $this->call(UserLevelSeeder::class);
        $this->call(ExchangeOffersTableSeeder::class);
        $this->call(SocialNetworksTableSeeder::class);
        $this->call(SettingSeeder::class);

        $this->call(BannerSeeder::class);
        $this->call(ReasonSeeder::class);
        $this->call(AdminsTableSeeder::class);

        $this->call(GameProfilesSeeder::class);
        $this->call(BountySeeder::class);
    }
}
