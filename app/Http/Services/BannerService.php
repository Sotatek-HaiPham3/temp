<?php

namespace App\Http\Services;

use App\Consts;
use App\Utils;
use App\Models\Banner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class BannerService extends BaseService {

    public function __construct()
    {
    }

    public function getBanners($params)
    {
        return Banner::where('is_active', Consts::TRUE)->get();
    }

}
