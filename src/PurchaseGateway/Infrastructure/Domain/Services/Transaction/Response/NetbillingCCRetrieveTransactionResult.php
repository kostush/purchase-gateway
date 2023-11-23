<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

use ProBillerNG\PurchaseGateway\Domain\Model\BillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransaction;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransactionBillerTransactions;

class NetbillingCCRetrieveTransactionResult extends RetrieveTransactionResult
{
    public const TYPE_AUTH = "auth";

    /**
     * @var string|null
     */
    private $cardHash;

    /**
     * @var string|null
     */
    private $cardDescription;

    /**
     * @var string|null
     */
    private $billerMemberId;

    /**
     * @var BillerTransactionCollection
     */
    private $billerTransactions = [];

    /**
     * Netbilling constructor.
     * @param RetrieveTransaction      $response               Response
     * @param MemberInformation        $memberInformation      Member information
     * @param CCTransactionInformation $transactionInformation Transaction information
     * @param NetbillingBillerFields   $billerFields           NetbillingBiller Fields
     */
    public function __construct(
        RetrieveTransaction $response,
        MemberInformation $memberInformation,
        CCTransactionInformation $transactionInformation,
        NetbillingBillerFields $billerFields
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

        $this->cardHash        = $response->getCardHash();
        $this->cardDescription = $response->getCardDescription();
        $this->billerMemberId  = $response->getBillerMemberId();
        $this->initBillerTransactions($response->getBillerTransactions());
    }

    /**
     * @return TransactionInformation|CCTransactionInformation
     */
    public function transactionInformation(): CCTransactionInformation
    {
        return $this->transactionInformation;
    }

    /**
     * @return BillerTransactionCollection
     */
    public function billerTransactions(): BillerTransactionCollection
    {
        return $this->billerTransactions;
    }

    /**
     * @return BillerFields|NetbillingBillerFields
     */
    public function billerFields(): NetbillingBillerFields
    {
        return $this->billerFields;
    }

    /**
     * @return null|string
     */
    public function cardDescription(): ?string
    {
        return $this->cardDescription;
    }

    /**
     * @return null|string
     */
    public function cardHash(): ?string
    {
        return $this->cardHash;
    }

    /**
     * @return string|null
     */
    public function billerMemberId(): ?string
    {
        return $this->billerMemberId;
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

            $status = $this->transactionInformation()->status() == Transaction::STATUS_APPROVED;

            if ($billerTransaction->getType() == self::TYPE_AUTH) {
                $status = true;
            }

            $billerTransactionsCollection->add(
                NetbillingBillerTransaction::create(
                    $billerTransaction->getCustomerId(),
                    $billerTransaction->getBillerTransactionId(),
                    $billerTransaction->getType(),
                    $status
                )
            );
        }
        $this->billerTransactions = $billerTransactionsCollection;
    }
}
