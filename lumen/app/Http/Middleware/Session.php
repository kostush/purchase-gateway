<?php

namespace App\Http\Middleware;

use App\Exceptions\InvalidRequestException;
use Closure;
use Illuminate\Http\Request;
use ProBillerNG\PurchaseGateway\Application\Services\TokenDecoder;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;

class Session
{
    /**
     * @var TokenDecoder
     */
    private $tokenDecoder;

    /**
     * Session constructor.
     * @param TokenDecoder $tokenDecoder The token decoder
     */
    public function __construct(TokenDecoder $tokenDecoder)
    {
        $this->tokenDecoder = $tokenDecoder;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request The http request
     * @param  \Closure                 $next    Closure
     * @throws \Exception
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $authToken = $request->bearerToken();
        $sessionId = $request->route('sessionId');

        //if we have both token and session id, fail
        if (!empty($authToken) && !empty($sessionId)) {
            throw new InvalidRequestException();
        }
        //if we only have token extract the session
        if (!empty($authToken) && empty($sessionId)) {
            $token     = $this->tokenDecoder->decode($authToken);
            $sessionId = $token->getSessionId();
            $request->attributes->set('decodedToken', $token);
            //set the session id on the route
            $this->setSessionIdOnRoute($request, $sessionId);
        }
        //if we don't have anything generate a session id
        if (empty($authToken) && empty($sessionId)) {
            $sessionId = (string) SessionId::cr-eate();
            //set the session id on the route
            $this->setSessionIdOnRoute($request, $sessionId);
        }
        
        //if the $sessionId is not empty, no action is required, it will be validated by the Logger trait

        $response = $next($request);

        return $response;
    }

    /**
     * @param Request $request   The request object
     * @param string  $sessionId The session uuid string
     * @return void
     */
    private function setSessionIdOnRoute(Request $request, string $sessionId): void
    {
        //set the session id on the route, so the logger class can retrieve it consistently with the other services
        $route                 = $request->route();
        $route[2]['sessionId'] = $sessionId;

        $request->setRouteResolver(
            function () use ($route) {
                return $route;
            }
        );

        //set the session id on the request attributes because for an unknown reason it can't be retrieved
        //form the request object on the controller
        $request->attributes->set('sessionId', $sessionId);
    }
}
