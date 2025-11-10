<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use App\Models\User;
use App\Models\Bounty;
use App\Utils;
use App\Consts;

class SearchingAPIController extends AppBaseController
{

    const LIMITATION_ITEMS = 10; // 10 items

    /**
    * @SWG\Get(
    *   path="/search",
    *   summary="Search users/games/bounties",
    *   tags={"Searching"},
    *   @SWG\Parameter(
    *       name="keyword",
    *       in="query",
    *       required=true,
    *       type="string"
    *   ),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function search(Request $request)
    {
        $request->validate([
            'keyword' => 'required'
        ]);

        $keyword = Utils::escapeLike($request->keyword);

        $users = User::where('status', Consts::USER_ACTIVE)
            ->where('username', 'LIKE', "%{$keyword}%")
            ->select('id', 'username', 'avatar', 'sex')
            ->take(static::LIMITATION_ITEMS)
            ->get();

        $bounties = Bounty::where('title', 'LIKE', "%{$keyword}%")
            ->with([
                'user' => function ($query) {
                    $query->select('id', 'username');
                }
            ])
            ->select('id', 'title', 'slug', 'price', 'media', 'user_id')
            ->take(static::LIMITATION_ITEMS)
            ->get();

        return $this->sendResponse([
            'users' => $users,
            'bounties' => $bounties
        ]);
    }
}
