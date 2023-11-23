<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBillerFields;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransaction;

class RocketgateCheckRetrieveTransactionResult extends RocketgateRetrieveTransactionResult
{
    /**
     * RocketgateCheckRetrieveTransactionResult constructor.
     *
     * @param RetrieveTransaction         $response
     * @param MemberInformation           $memberInformation
     * @param CheckTransactionInformation $transactionInformation
     * @param RocketgateBillerFields      $billerFields
     */
    public function __construct(
        RetrieveTransaction $response,
        MemberInformation $memberInformation,
        CheckTransactionInformation $transactionInformation,
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

        $this->merchantId       = $response->getMerchantId();
        $this->merchantPassword = $response->getMerchantPassword();
        $this->invoiceId        = $response->getInvoiceId();
        $this->customerId       = $response->getCustomerId();
        $this->merchantAccount  = $response->getMerchantAccount();
        $this->initBillerTransactions($response->getBillerTransactions());
    }

    /**
     * @return TransactionInformation|CheckTransactionInformation
     */
    public function transactionInformation(): CheckTransactionInformation
    {
        return $this->transactionInformation;
    }
}