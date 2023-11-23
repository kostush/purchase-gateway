<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\RetrieveFailedBillers;

use ProBillerNG\Base\Application\Services\Query;

class RetrieveFailedBillersQuery extends Query
{
    /**
     * @var string
     */
    private $sessionId;

    /**
     * RetrieveFailedBillersQuery constructor.
     * @param string $sessionId The session id
     */
    public function __construct(string $sessionId)
    {
        $this->sessionId = $sessionId;
    }

    /**
     * @return string
     */
    public function sessionId(): string
    {
        return $this->sessionId;
    }
}
