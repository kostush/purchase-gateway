<?php

namespace App\Http\Middleware;

use App\Logger;
use Closure;
use ProBillerNG\Logger\Log;

class NGLogger
{
    use Logger;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request The http request
     * @param  \Closure                 $next    Closure
     * @return mixed
     * @throws \ProBillerNG\Logger\Exception
     */
    public function handle($request, Closure $next)
    {
        $this->initLogger('APP_LOG_FILE', $request);

        if ($request->header('ignore-log') !== "1") {
            Log::logRequest(
                $request,
                [
                    'payment.ccNumber',
                    'payment.cvv',
                    'payment.cardInformation.ccNumber',
                    'payment.cardInformation.cvv',
                    'payment.checkInformation.routingNumber',
                    'payment.checkInformation.accountNumber',
                    'payment.checkInformation.savingAccount',
                    'payment.checkInformation.socialSecurityLast4',
                    'member.password',
                ],
                ['x-api-key']
            );
        }

        $response = $next($request);

        if ($request->header('ignore-log') !== "1") {
            Log::logResponse($response);
        }

        return $response;
    }
}
