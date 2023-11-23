<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Services\Exception\FailedBillersNotFoundException;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\InitializedItemsCollectionNotFoundException;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\PurchaseWasSuccessfulException;

class FailedBillers
{
    /**
     * @var array
     */
    private $failedBillers = [];

    /**
     * FailedBillers constructor.
     * @param InitializedItemCollection $initializedItemCollection The item collection
     * @throws FailedBillersNotFoundException
     * @throws PurchaseWasSuccessfulException
     * @throws \ProBillerNG\Logger\Exception
     */
    private function __construct(InitializedItemCollection $initializedItemCollection)
    {
        $this->initFailedBillers($initializedItemCollection);
    }

    /**
     * @param InitializedItemCollection $initializedItemCollection The Item collection
     * @throws FailedBillersNotFoundException
     * @throws PurchaseWasSuccessfulException
     * @throws \ProBillerNG\Logger\Exception
     * @return void
     */
    private function initFailedBillers(InitializedItemCollection $initializedItemCollection): void
    {
        /** @var InitializedItem $initializedItem */
        foreach ($initializedItemCollection as $initializedItem) {
            if ($initializedItem->wasItemPurchaseSuccessful()) {
                throw new PurchaseWasSuccessfulException();
            }
            $this->extractFailedBillersFromTransactionCollection($initializedItem->transactionCollection());
        }

        $this->failedBillers = array_values($this->failedBillers);

        if (empty($this->failedBillers)) {
            throw new FailedBillersNotFoundException();
        }
    }

    /**
     * @param InitializedItemCollection $initializedItemCollection The purchase session
     * @return self
     * @throws \Exception
     */
    public static function createFromInitializedItemCollection(
        InitializedItemCollection $initializedItemCollection
    ): self {
        if ($initializedItemCollection->count() == 0) {
            throw new InitializedItemsCollectionNotFoundException();
        }

        return new static($initializedItemCollection);
    }

    /**
     * @param TransactionCollection $transactionCollection The Initialized item
     * @return void
     */
    private function extractFailedBillersFromTransactionCollection(TransactionCollection $transactionCollection): void
    {
        if (count($transactionCollection) == 0) {
            return;
        }

        /** @var Transaction $transaction */
        foreach ($transactionCollection as $transaction) {
            $this->failedBillers[$transaction->billerName()] = ['billerName' => $transaction->billerName()];
        }
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->failedBillers;
    }
}
