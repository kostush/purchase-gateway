<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use ProBillerNG\PurchaseGateway\Application\Services\TokenDecoder;

class Correlation
{
    /**
     * @var TokenDecoder
     */
    private $tokenDecoder;

    /**
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
        if ($authToken !== null) {
            $token         = $this->tokenDecoder->decode($authToken);
            $correlationId = $token->getCorrelationId();
            if ($correlationId !== null) {
                $request->headers->set('X-CORRELATION-ID', $correlationId);

                // we need to set header as server variable so that it'd be populated
                // inside of custom FormRequests created in commands
                $request->server->set('HTTP_X-CORRELATION-ID', $correlationId);
            }
        }

        return $next($request);
    }
}
