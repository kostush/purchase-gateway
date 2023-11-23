<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;

interface CascadeTranslatingService
{
    /**
     * @var string
     */
    public const TEST_NETBILLING = 'test-netbilling';

    /**
     * @var string
     */
    public const TEST_ROCKETGATE = 'test-rocketgate';

    /**
     * @var string
     */
    public const TEST_EPOCH = 'test-epoch';

    /**
     * @var string
     */
    public const TEST_QYSSO = 'test-qysso';

    /**
     * @param string      $sessionId       Session Id
     * @param string      $siteId          Site Id
     * @param string      $businessGroupId Business Group Id
     * @param string      $country         Country
     * @param string      $paymentType     Payment type
     * @param string|null $paymentMethod   Payment method
     * @param string|null $trafficSource   Traffic source
     * @return Cascade
     */
    public function retrieveCascadeForInitPurchase(
        string $sessionId,
        string $siteId,
        string $businessGroupId,
        string $country,
        string $paymentType,
        ?string $paymentMethod,
        ?string $trafficSource
    ): Cascade;
}
