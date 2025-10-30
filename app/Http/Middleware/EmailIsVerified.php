<?php

namespace App\Http\Middleware;

use Closure;
use App\Exceptions\Reports\EmailNotVerifiedException;

class EmailIsVerified
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
        if (!$request->user() || !$request->user()->hasVerifiedEmail()) {
            throw new EmailNotVerifiedException();
        }
        return $next($request);
    }
}
