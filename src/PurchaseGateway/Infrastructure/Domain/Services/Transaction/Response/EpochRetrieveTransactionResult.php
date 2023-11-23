<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

use ProBillerNG\PurchaseGateway\Domain\Model\BillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBillerFields;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransaction;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransactionBillerTransactions;

class EpochRetrieveTransactionResult extends RetrieveTransactionResult
{
    /**
     * @var string|null
     */
    private $billerTransactionId;

    /**
     * @var BillerTransactionCollection
     */
    private $billerTransactions = [];

    /**
     * Payment method
     * @var string
     */
    private $paymentSubType;

    /**
     * EpochCCRetrieveTransactionResult constructor.
     * @param RetrieveTransaction    $response               Response
     * @param MemberInformation      $memberInformation      Member information
     * @param TransactionInformation $transactionInformation Transaction information
     * @param EpochBillerFields      $billerFields           Biller fields
     */
    public function __construct(
        RetrieveTransaction $response,
        MemberInformation $memberInformation,
        TransactionInformation $transactionInformation,
        EpochBillerFields $billerFields
    ) {
        parent::__construct(
            $response->getBillerId(),
            $transactionInformation->billerName(),
            $response->getTransactionId(),
            $response->getCurrency(),
            $response->getSiteId(),
            $response->getPaymentType(),
            $memberInformation,
            $transactionInformation,
            $billerFields
        );

        $this->initBillerTransactionId($response->getBillerTransactions());
        $this->initBillerTransactions($response->getBillerTransactions());
        $this->paymentSubType = $response->getPaymentMethod();
    }

    /**
     * @param array|null $billerTransactions Biller transactions
     * @return void
     */
    private function initBillerTransactionId(?array $billerTransactions): void
    {
        $this->billerTransactionId = !empty($billerTransactions)
            ? end($billerTransactions)->getBillerTransactionId() : null;
    }

    /**
     * @param array $billerTransactions The biller transactions array
     * @return void
     */
    private function initBillerTransactions(array $billerTransactions): void
    {
        $billerTransactionsCollection = new BillerTransactionCollection();
        /** @var RetrieveTransactionBillerTransactions $billerTransaction */
        foreach ($billerTransactions as $billerTransaction) {
            if (is_null($billerTransaction->getBillerTransactionId())) {
                continue;
            }

            $billerTransactionsCollection->add(
                EpochBillerTransaction::create(
                    $billerTransaction->getPiCode(),
                    $billerTransaction->getBillerMemberId(),
                    $billerTransaction->getBillerTransactionId(),
                    $billerTransaction->getAns()
                )
            );
        }
        $this->billerTransactions = $billerTransactionsCollection;
    }

    /**
     * @return BillerFields
     */
    public function billerFields(): BillerFields
    {
        return $this->billerFields;
    }

    /**
     * @return TransactionInformation|CCTransactionInformation
     */
    public function transactionInformation()
    {
        return $this->transactionInformation;
    }

    /**
     * @return bool
     */
    public function securedWithThreeD(): bool
    {
        return false;
    }

    /**
     * @return null|int
     */
    public function threeDSecureVersion(): ?int
    {
        return null;
    }

    /**
     * @return BillerTransactionCollection
     */
    public function billerTransactions(): BillerTransactionCollection
    {
        return $this->billerTransactions;
    }

    /**
     * @return string
     */
    public function paymentSubtype(): string
    {
        return $this->paymentSubType;
    }

    /**
     * @return string|null
     */
    public function billerTransactionId(): ?string
    {
        return $this->billerTransactionId;
    }
}
