<?php

namespace App\Console\Commands;

use App\Jobs\CreateMattermostUserEndpoint;
use Illuminate\Console\Command;
use App\Jobs\BountyCheckReady;
use App\Models\MattermostUser;
use App\Models\User;
use App\Utils;
use Mattermost;
use DB;

class FixMattermostTeamUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mattermost-team-user:fix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixing the mattermost user missing team on application';


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (Utils::isProduction() && !$this->confirm('Do you wish to continue?')) {
            return;
        }

        DB::beginTransaction();
        try {
            $mattermostUserIds = MattermostUser::pluck('mattermost_user_id')->all();
            $mattermostTeamUsers = Mattermost::getTeamMembersByIds($mattermostUserIds);

            $mattermostUserIdsExistsInTeam = collect($mattermostTeamUsers)
                ->pluck('user_id')
                ->all();

            $mattermostUserIdsNotExistsInTeam = MattermostUser::whereNotIn('mattermost_user_id', $mattermostUserIdsExistsInTeam)
                ->pluck('mattermost_user_id');
            if ($mattermostUserIdsNotExistsInTeam->isEmpty()) {
                logger()->info('===============Not exists mattermost user not inside team.===============');
                return;
            }

            logger()->info('===============Mattermost user ids not inside team===============:', [$mattermostUserIdsNotExistsInTeam->all()]);
            Mattermost::addUsersIntoMattermostTeam($mattermostUserIdsNotExistsInTeam);

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
