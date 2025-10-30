<?php

namespace App\Http\Middleware;

use Closure;
use Carbon\Carbon;
use Auth;

class UserActivate
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
        $user = Auth::user();
        if ($user) {
            $user->last_time_active = Carbon::now();
            $user->save();
        }

        return $next($request);
    }
}
