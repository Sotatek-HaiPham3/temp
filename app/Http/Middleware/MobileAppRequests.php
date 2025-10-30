<?php

namespace App\Http\Middleware;

use Closure;
use Mobile_Detect;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\Reports\MobileAppRequestException;

class MobileAppRequests
{
    const DELIMITER_CHAR = '-';
    const PREFIX_AGENT = 'Gamelancer';

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Format: [Gamelancer]-[Device_Name]-[Version]
        $userAgent = $request->header('Custom-User-Agent');

        $keys = explode(static::DELIMITER_CHAR, base64_decode($userAgent));

        if (count($keys) === 3 && $keys[0] === static::PREFIX_AGENT) {
            return $next($request);
        }
        throw new MobileAppRequestException();
    }
}
