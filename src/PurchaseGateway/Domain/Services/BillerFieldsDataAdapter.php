<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\BillerFields;

abstract class BillerFieldsDataAdapter
{
    /**
     * @param array $billerFieldsData Biller Fields Data
     * @return BillerFields
     */
    abstract public function convert(array $billerFieldsData): BillerFields;
}