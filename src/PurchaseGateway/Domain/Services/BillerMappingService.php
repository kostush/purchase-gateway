<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;

interface BillerMappingService
{
    /**
     * @param string $billerName      Biller Name.
     * @param string $businessGroupId Business group Id
     * @param string $siteId          Site UUID
     * @param string $currencyCode    Currency Code
     * @param string $sessionId       SessionInfo UUID
     *
     * @return BillerMapping
     */
    public function retrieveBillerMapping(
        string $billerName,
        string $businessGroupId,
        string $siteId,
        string $currencyCode,
        string $sessionId
    ): BillerMapping;
}