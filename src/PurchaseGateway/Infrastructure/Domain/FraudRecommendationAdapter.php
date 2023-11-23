<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain;

use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendationCollection;

interface FraudRecommendationAdapter
{
    /**
     * @param string $businessGroupId
     * @param string $siteId
     * @param string $event
     * @param array  $data
     * @param string $sessionId
     * @param array  $fraudHeaders
     *
     * @return FraudRecommendationCollection
     */
    public function retrieve(
        string $businessGroupId,
        string $siteId,
        string $event,
        array $data,
        string $sessionId,
        array $fraudHeaders
    ): FraudRecommendationCollection;
}
