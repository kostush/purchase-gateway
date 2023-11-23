<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBillerFields;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransactionBillerTransactions;

abstract class RocketgateRetrieveTransactionResult extends RetrieveTransactionResult
{
    /**
     * @var string
     */
    protected $merchantId;

    /**
     * @var string
     */
    protected $merchantPassword;

    /**
     * @deprecated
     * @var string
     */
    protected $invoiceId;

    /**
     * @deprecated
     * @var string
     */
    protected $customerId;

    /**
     * @var string|null
     */
    protected $merchantAccount;

    /**
     * @var BillerTransactionCollection
     */
    protected $billerTransactions = [];

    /**
     * @var bool
     */
    protected $securedWithThreeD = false;

    /**
     * @var int|null
     */
    protected $threeDSecureVersion = null;

    /**
     * @return string
     */
    public function merchantId(): string
    {
        return $this->merchantId;
    }

    /**
     * @return string
     */
    public function merchantPassword(): string
    {
        return $this->merchantPassword;
    }

    /**
     * @return string
     * @deprecated
     */
    public function invoiceId(): string
    {
        return $this->invoiceId;
    }

    /**
     * @return string
     * @deprecated
     */
    public function customerId(): string
    {
        return $this->customerId;
    }

    /**
     * @return null|string
     */
    public function merchantAccount(): ?string
    {
        return $this->merchantAccount;
    }

    /**
     * @return BillerFields|RocketgateBillerFields
     */
    public function billerFields(): RocketgateBillerFields
    {
        return $this->billerFields;
    }

    /**
     * @return BillerTransactionCollection
     */
    public function billerTransactions(): BillerTransactionCollection
    {
        return $this->billerTransactions;
    }

    /**
     * @return bool
     */
    public function securedWithThreeD(): bool
    {
        return $this->securedWithThreeD;
    }

    /**
     * @param array $billerTransactions The biller transactions array
     *
     * @return void
     * @throws Exception
     */
    protected function initBillerTransactions(array $billerTransactions): void
    {
        $billerTransactionsCollection = new BillerTransactionCollection();
        /** @var RetrieveTransactionBillerTransactions $billerTransaction */
        foreach ($billerTransactions as $billerTransaction) {
            if (empty($billerTransaction->getBillerTransactionId())) {
                Log::info('Biller transaction id is empty.');
                continue;
            }

            $billerTransactionsCollection->add(
                RocketgateBillerTransaction::create(
                    $billerTransaction->getInvoiceId(),
                    $billerTransaction->getCustomerId(),
                    $billerTransaction->getBillerTransactionId(),
                    $billerTransaction->getType()
                )
            );
        }
        $this->billerTransactions = $billerTransactionsCollection;
    }

    /**
     * @return int|null
     */
    public function threeDSecureVersion(): ?int
    {
        return $this->threeDSecureVersion;
    }
}