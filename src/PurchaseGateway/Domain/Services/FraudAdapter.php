<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;

interface FraudAdapter
{
    /**
     * @param SiteId         $siteId    Site id
     * @param array          $params    Params
     * @param string         $for       For which step
     * @param SessionId|null $sessionId Session id
     *
     * @return FraudAdvice
     */
    public function retrieveAdvice(
        SiteId $siteId,
        array $params,
        string $for,
        SessionId $sessionId = null
    ): FraudAdvice;
}
