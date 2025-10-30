<?php

namespace App\Http\Middleware;

use Closure;
use App\Exceptions\Reports\PhoneNotVerifiedException;

class PhoneIsVerified
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
        if (!$request->user() || !$request->user()->hasVerifiedPhone()) {
            throw new PhoneNotVerifiedException();
        }
        return $next($request);
    }
}
