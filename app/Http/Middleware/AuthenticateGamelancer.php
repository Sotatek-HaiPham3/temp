<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\Reports\PermissionDenyException;

class AuthenticateGamelancer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check() && Auth::user()->isGamelancer) {
            return $next($request);
        }
        throw new PermissionDenyException();
    }
}
