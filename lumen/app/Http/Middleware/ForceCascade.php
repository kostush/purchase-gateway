<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Exception\ForceCascadeException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Cascade\RetrieveCascadeTranslatingService;

class ForceCascade
{
    /**
     * @param Request  $request Request
     * @param \Closure $next    Next
     * @return mixed
     * @throws ForceCascadeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function handle($request, \Closure $next)
    {
        // get force cascade
        $forceCascade = $request->header('x-force-cascade');

        if ($this->forceCascadeIsInvalid($forceCascade)) {
            throw new ForceCascadeException();
        }

        return $next($request);
    }

    /**
     * @param string $forceCascade Force cascade parameter
     * @return bool
     */
    private function forceCascadeIsInvalid(?string $forceCascade): bool
    {
        return !empty($forceCascade)
               && $forceCascade !== RetrieveCascadeTranslatingService::TEST_ROCKETGATE
               && $forceCascade !== RetrieveCascadeTranslatingService::TEST_NETBILLING
               && $forceCascade !== RetrieveCascadeTranslatingService::TEST_EPOCH
               && $forceCascade !== RetrieveCascadeTranslatingService::TEST_QYSSO;
    }
}
