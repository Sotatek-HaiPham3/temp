<?php

namespace App\Http\Middleware;

use Closure;
use App\Exceptions\Reports\AccountNotActivedException;

class AccountIsVerified
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
        if (!$request->user() || !$request->user()->isAccountVerified()) {
            throw new AccountNotActivedException();
        }
        return $next($request);
    }
}
