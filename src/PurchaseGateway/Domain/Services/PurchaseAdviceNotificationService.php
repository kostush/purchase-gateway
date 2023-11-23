<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

interface PurchaseAdviceNotificationService
{
    /**
     * @param string $siteId        Site Id.
     * @param string $taxType       Tax Type.
     * @param string $sessionId     SessionId
     * @param string $billerName    Biller Name.
     * @param string $memberType    MemberType
     * @param string $transactionId Transaction Id
     * @return bool
     * @throws \ProBillerNG\Logger\Exception
     */
    public function getAdvice(
        string $siteId,
        string $taxType,
        string $sessionId,
        string $billerName,
        string $memberType,
        string $transactionId
    ): bool;
}
