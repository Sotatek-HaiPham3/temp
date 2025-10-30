<?php

use Illuminate\Database\Seeder;
use App\Models\Setting;
use App\Consts;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('settings')->truncate();

        $mattermostTeamId = Mattermost::createMattermostTeam();
        $this->createData(Consts::MATTERMOST_TEAM_ID_KEY, $mattermostTeamId);

        foreach (Consts::SETTINGS as $key => $value) {
            $this->createData($key, $value);
        }
    }

    private function createData($key, $value)
    {
        Setting::create([
            'key'       => $key,
            'value'     => $value
        ]);
    }
}
