<?php

namespace App\Http\Controllers;

use App\Consts;
use App\Utils;
use App\Http\Services\MasterdataService;
use App\Http\Services\UserService;
use Illuminate\Http\Request;
use Auth;
use Cache;
use DB;
use Exception;
use Log;
use Crypt;
use App\Models\User;
use App\Models\GameBounty;

class HomeController extends Controller
{

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return view('layouts.app');
    }

    public function getTermAndPrivacy()
    {
        return view('layouts.term_and_privacy');
    }

    public function getContactUs()
    {
        return view('layouts.contact_us');
    }
}
