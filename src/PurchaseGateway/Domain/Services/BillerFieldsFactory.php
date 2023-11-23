<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\BillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\Biller;

interface BillerFieldsFactory
{
    /**
     * @param Biller $biller           Biller
     * @param array  $billerFieldsData Biller Fields Data
     * @return BillerFields
     */
    public static function create(Biller $biller, array $billerFieldsData): BillerFields;
}
