<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\Biller;

interface BillerFactory
{
    /**
     * @param string $billerName Biller Name
     * @return Biller
     */
    public static function create(string $billerName): Biller;

    /**
     * @param string $forceCascade Force Cascade
     * @return Biller
     */
    public static function createFromForceCascade(string $forceCascade): Biller;

    /**
     * @param string $billerId Biller Id
     * @return Biller
     */
    public static function createFromBillerId(string $billerId): Biller;
}
