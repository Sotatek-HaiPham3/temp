<?php

namespace App\Http\Middleware;

use Closure;
use App\Utils;

class LoggingRequestBody
{

    protected $maskAttributes = [
        'password',
        'password_confirmation'
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param string $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $content = sprintf('========== Request body [%s %s - %s] ', request()->method(), request()->path(), getOriginalClientIp());

        logger()->info($content, Utils::maskData($request->all(), $this->maskAttributes));

        return $next($request);
    }
}
