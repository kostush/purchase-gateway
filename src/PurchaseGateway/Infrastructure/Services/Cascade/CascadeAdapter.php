<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Services\Cascade;

use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;

interface CascadeAdapter
{
    /**
     * @param string      $sessionId       Session Id
     * @param string      $siteId          Site Id
     * @param string      $businessGroupId Business Group Id
     * @param string      $country         Country
     * @param string      $paymentType     Payment type
     * @param string|null $paymentMethod   Payment method
     * @param string|null $trafficSource   Traffic source
     * @return mixed
     */
    public function get(
        string $sessionId,
        string $siteId,
        string $businessGroupId,
        string $country,
        string $paymentType,
        ?string $paymentMethod,
        ?string $trafficSource
    ): Cascade;
}
