<?php

namespace App\Http\Middleware;

use \Closure;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\HeaderBag;

class GenerateCorrelationId
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
        $correlationId = $this->validateCorrelationId($request->headers->get('X-CORRELATION-ID'));
        $request->headers->set('X-CORRELATION-ID', $correlationId);

        // we need to set header as server variable so that it'd be populated
        // inside of custom FormRequests created in commands
        $request->server->set('HTTP_X-CORRELATION-ID', $correlationId);

        return $next($request);
    }

    /**
     * @param string|null $correlationId
     * @return string
     * @throws \Exception
     */
    protected function validateCorrelationId(?string $correlationId) : string
    {
        if (empty($correlationId) || !Uuid::isValid($correlationId)) {
            return (string) Uuid::uuid4();
        }

        return $correlationId;
    }
}
