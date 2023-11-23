<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Cascade;

use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;
use ProBillerNG\PurchaseGateway\Domain\Services\CascadeTranslatingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Services\Cascade\CascadeAdapter;

class RetrieveCascadeTranslatingService implements CascadeTranslatingService
{
    /**
     * @var CascadeAdapter
     */
    private $cascadeAdapter;

    /**
     * RetrieveCascadeTranslatingService constructor.
     * @param CascadeAdapter $cascadeAdapter Cascade Adapter
     */
    public function __construct(CascadeAdapter $cascadeAdapter)
    {
        $this->cascadeAdapter = $cascadeAdapter;
    }

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
    ): Cascade {
        return $this->cascadeAdapter->get(
            $sessionId,
            $siteId,
            $businessGroupId,
            $country,
            $paymentType,
            $paymentMethod,
            $trafficSource
        );
    }
}
