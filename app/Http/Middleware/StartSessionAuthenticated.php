<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Session\Middleware\StartSession;
use App\Http\Middleware\Supports\SessionManager;
use Auth;
use Illuminate\Support\Facades\Route;

class StartSessionAuthenticated extends StartSession
{

    protected $shouldResetSession = [
        'PUT api/logout',
        'PUT api/v1/logout',
        'GET api/v1/ping',
    ];

    /**
     * Create a new session middleware.
     *
     * @param  \App\Http\Middleware\Supports\SessionManager  $manager
     * @return void
     */
    public function __construct(SessionManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // ignore for admin route.
        if ($this->isAdminRoute()) {
            return $next($request); 
        }

        $response = parent::handle($request, $next);

        if ($this->shouldEmptySession($request)) {
            $this->manager->driver()->getHandler()->destroy(
                $this->manager->driver()->getId()
            );

            // re-generate session id
            $session = $this->startSession($request);
            $session->setId(null);
            $this->addCookieToResponse($response, $session);
        }

        return $response;
    }

    /**
     * Save the session data to storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function saveSession($request)
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        $this->manager->driver()->save();
    }

    private function isAuthenticated()
    {
        return Auth::guard('api')->check();
    }

    private function shouldEmptySession($request)
    {
        $keyword = strtolower(sprintf('%s %s', request()->method(), request()->path()));

        return collect($this->shouldResetSession)->contains(function ($item) use ($keyword) {
            return strtolower($item) === $keyword;
        });
    }

    private function isAdminRoute()
    {
        return strpos(request()->path(), 'admin/');
    }
}
