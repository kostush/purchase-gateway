<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Intercepts modification resource methods in order to try JSON data before regular input data
 * @package App\Http\Middleware
 * @see https://medium.com/@paulredmond/how-to-submit-json-post-requests-to-lumen-666257fe8280
 */
class JsonRequestMiddleware
{
    /**
     * @param Request $request Request Payload.
     * @param Closure $next    Nest Request Handler
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH']) && $request->isJson()) {
            $request->request->replace($request->json()->all() ?? []);
        }
        return $next($request);
    }
}
