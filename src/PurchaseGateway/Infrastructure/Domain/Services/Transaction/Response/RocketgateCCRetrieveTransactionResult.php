<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBillerFields;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransaction;

class RocketgateCCRetrieveTransactionResult extends RocketgateRetrieveTransactionResult
{
    /**
     * @var string|null
     */
    private $cardHash;

    /**
     * @var string|null
     */
    private $cardDescription;

    /**
     * Rocketgate constructor.
     *
     * @param RetrieveTransaction      $response               Response
     * @param MemberInformation        $memberInformation      Member information
     * @param CCTransactionInformation $transactionInformation Transaction information
     * @param RocketgateBillerFields   $billerFields           The biller fields
     */
    public function __construct(
        RetrieveTransaction $response,
        MemberInformation $memberInformation,
        CCTransactionInformation $transactionInformation,
        RocketgateBillerFields $billerFields
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

        $this->merchantId          = $response->getMerchantId();
        $this->merchantPassword    = $response->getMerchantPassword();
        $this->invoiceId           = $response->getInvoiceId();
        $this->customerId          = $response->getCustomerId();
        $this->cardHash            = $response->getCardHash();
        $this->merchantAccount     = $response->getMerchantAccount();
        $this->cardDescription     = $response->getCardDescription();
        $this->securedWithThreeD   = $response->getSecuredWithThreeD();
        $this->threeDSecureVersion = $response->getThreedSecuredVersion();
        $this->initBillerTransactions($response->getBillerTransactions());
    }

    /**
     * @return null|string
     */
    public function cardHash(): ?string
    {
        return $this->cardHash;
    }

    /**
     * @return null|string
     */
    public function cardDescription(): ?string
    {
        return $this->cardDescription;
    }

    /**
     * @return TransactionInformation|CCTransactionInformation
     */
    public function transactionInformation(): CCTransactionInformation
    {
        return $this->transactionInformation;
    }
}
