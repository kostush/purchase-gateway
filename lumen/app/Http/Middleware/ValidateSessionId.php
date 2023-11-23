<?php

namespace App\Http\Middleware;

use App\Exceptions\InvalidSessionIdException;
use Closure;
use Ramsey\Uuid\Uuid;

class ValidateSessionId
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request The http request
     * @param  \Closure                 $next    Closure
     * @return mixed
     * @throws \App\Exceptions\InvalidSessionIdException
     */
    public function handle($request, Closure $next)
    {
        $sessionId = $request->route('sessionId');

        if (empty($sessionId) || !Uuid::isValid($sessionId)) {
            throw new InvalidSessionIdException();
        }

        $request->attributes->set('sessionId', $sessionId);

        $response = $next($request);

        return $response;
    }
}