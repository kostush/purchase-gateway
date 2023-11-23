<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\RetrieveBillerTransaction;

use ProBillerNG\Base\Application\Services\Query;

class RetrieveItemQuery extends Query
{
    /**
     * @var string
     */
    private $itemId;

    /**
     * @var string
     */
    private $sessionId;

    /**
     * RetrieveItemQuery constructor.
     * @param string $itemId    Item Id
     * @param string $sessionId Session Id
     */
    public function __construct(string $itemId, string $sessionId)
    {
        $this->itemId    = $itemId;
        $this->sessionId = $sessionId;
    }

    /**
     * @return string
     */
    public function itemId(): string
    {
        return $this->itemId;
    }

    /**
     * @return string
     */
    public function sessionId(): string
    {
        return $this->sessionId;
    }
}
