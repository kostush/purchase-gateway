<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

use ProBillerNG\PurchaseGateway\Domain\Model\BillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBillerFields;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransaction;

class EmptyRetrieveTransactionResult extends RetrieveTransactionResult
{
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
    }

    /**
     * @return BillerFields
     */
    public function billerFields(): BillerFields
    {
        return $this->billerFields;
    }

    /**
     * @return TransactionInformation|EmptyTransactionInformation
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
     * @return int|null
     */
    public function threeDSecureVersion(): ?int
    {
        return null;
    }
}
