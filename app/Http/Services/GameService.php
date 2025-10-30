<?php

namespace App\Http\Services;

use App\Models\Game;
use App\Models\Platform;
use App\Models\GameBounty;
use App\Models\GameBountyImage;
use App\Models\GameBountySchedule;
use App\Models\UserBounty;
use App\Models\UserGame;
use App\Models\Language;
use App\Models\BountyType;
use App\Consts;
use App\Models\User;
use App\Utils;
use App\Utils\BigNumber;
use Auth;
use Exception;
use App\Http\Services\UserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GameService extends BaseService {

    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function getGames($input)
    {
        return Game::select('id', 'title', 'cover')
            ->when(!empty($input['search_key']), function ($query) use ($input) {
                $searchKey = $input['search_key'];
                return $query->where('title', 'like', "%{$searchKey}%");
            })
            ->paginate(array_get($input, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function validExistsGame($id)
    {
        $existsGame = Game::where('id', $id)->exists();
        if (!$existsGame) {
            throw new Exception('You cannot create this bounty because this game was deleted by admin.');
        }
    }

    public function validExistsPlatform($id)
    {
        $existsPlatform = Platform::where('id', $id)->exists();
        if (!$existsPlatform) {
            throw new Exception('You cannot create this bounty because this platform was deleted by admin.');
        }
    }

    private function validBeforeUserGame($data)
    {
        $this->validExistsGame($data['game_id']);
        $this->validExistsPlatform($data['platform_id']);
    }

    private function toNumber($value)
    {
        return BigNumber::new($value)->toString();
    }

}
