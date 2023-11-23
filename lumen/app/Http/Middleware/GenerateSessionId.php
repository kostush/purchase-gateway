<?php

namespace App\Http\Middleware;

use Closure;
use Ramsey\Uuid\Uuid;

class GenerateSessionId
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request The http request
     * @param  \Closure                 $next    Closure
     * @return mixed
     * @throws \Exception
     */
    public function handle($request, Closure $next)
    {
        $request->attributes->set(
            'sessionId',
            $this->initSession ($request->route('sessionId'))
        );

        $response = $next($request);
        return $response;
    }

    /**
     * @param string|null $requestSessionId
     * @return string
     * @throws \Exception
     */
    protected function initSession (?string $requestSessionId) : string
    {
        if (empty($requestSessionId) || !Uuid::isValid($requestSessionId)) {
            return (string) Uuid::uuid4();
        }

        return $requestSessionId;
    }
}