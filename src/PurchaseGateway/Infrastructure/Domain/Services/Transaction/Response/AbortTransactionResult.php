<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

use ProbillerNG\TransactionServiceClient\Model\AbortTransactionResponse;

class AbortTransactionResult
{
    /**
     * @var string|null
     */
    private $status;

    /**
     * AbortTransactionResult constructor.
     * @param AbortTransactionResponse $response Api response
     */
    public function __construct(
        AbortTransactionResponse $response
    ) {
        $this->status = $response->getStatus();
    }

    /**
     * @return null|string
     */
    public function status(): ?string
    {
        return $this->status;
    }
}
