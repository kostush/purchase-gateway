<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\PurchaseGatewayHealth;

use ProBillerNG\Base\Application\Services\Query;

class RetrievePurchaseGatewayHealthQuery extends Query
{
    /**
     * @var bool
     */
    private $retrievePostbackStatus;

    /**
     * RetrievePurchaseGatewayHealthQuery constructor.
     * @param bool $retrievePostbackStatus Postback status retrieval flag
     */
    public function __construct($retrievePostbackStatus = false)
    {
        $this->retrievePostbackStatus = (bool) $retrievePostbackStatus === true;
    }

    /**
     * @return bool
     */
    public function retrievePostbackStatus(): bool
    {
        return $this->retrievePostbackStatus;
    }
}
