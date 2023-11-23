<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\BillerFactoryService;

class ThirdPartyRebillTransaction
{

    /**
     * @var TransactionId
     */
    private $transactionId;

    /**
     * @var string
     */
    private $state;

    /**
     * Transaction constructor.
     * @param null|TransactionId $transactionId Transaction Id
     * @param string             $state         State
     * @throws \Exception
     */
    private function __construct(
        ?TransactionId $transactionId,
        string $state
    ) {
        $this->transactionId = $transactionId;
        $this->setState($state);
    }

    /**
     * @param string $state The transaction status
     * @return void
     * @throws \Exception
     */
    public function setState(string $state): void
    {
        $this->state = $state;
    }

    /**
     * @param null|TransactionId $transactionId Transaction Id
     * @param string             $state         State
     * @return ThirdPartyRebillTransaction
     * @throws \Exception
     */
    public static function create(
        ?TransactionId $transactionId,
        string $state
    ): self {
        return new static(
            $transactionId,
            $state
        );
    }

    /**
     * @return null|TransactionId
     */
    public function transactionId(): ?TransactionId
    {
        return $this->transactionId;
    }

    /**
     * @return string
     */
    public function state(): string
    {
        return $this->state;
    }
}
