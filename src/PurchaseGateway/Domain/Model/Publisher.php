<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

interface Publisher
{
    /**
     * @param string    $transactionId       Transaction id
     * @param string    $siteId              Site id
     * @param array     $billerFields        Biller fields
     * @param array     $subsequentOperation Subsequent operation
     * @param array     $paymentInformation  Payment information
     * @param SessionId $sessionId           Session id
     * @return void
     */
    public function publishTransactionToBeVoided(
        string $transactionId,
        string $siteId,
        array $billerFields,
        array $subsequentOperation,
        array $paymentInformation,
        SessionId $sessionId
    ): void;
}
