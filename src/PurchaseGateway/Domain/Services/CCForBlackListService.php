<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;

interface CCForBlackListService
{
    /**
     * @param string           $firstSix        First Six
     * @param string           $lastFour        Last Four
     * @param string           $expirationMonth Expiration Month
     * @param string           $expirationYear  Expiration Year
     * @param string           $sessionId       Session id
     * @param Transaction|null $transaction     Transaction
     * @return bool
     */
    public function addCCForBlackList(
        string $firstSix,
        string $lastFour,
        string $expirationMonth,
        string $expirationYear,
        string $sessionId,
        ?Transaction $transaction
    ): bool;

    /**
     * @param string $firstSix        First Six
     * @param string $lastFour        Last Four
     * @param string $expirationMonth Expiration Month
     * @param string $expirationYear  Expiration Year
     * @param string $sessionId       Session id
     * @return bool
     */
    public function checkCCForBlacklist(
        string $firstSix,
        string $lastFour,
        string $expirationMonth,
        string $expirationYear,
        string $sessionId
    ): bool;
}
