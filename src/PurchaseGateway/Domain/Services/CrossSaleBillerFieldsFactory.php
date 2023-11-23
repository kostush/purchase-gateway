<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\BillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBillerFields;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\BillerNotSupportedException;

class CrossSaleBillerFieldsFactory
{
    /**
     * For CrossSales we want a new invoiceId to be generated
     * so we have to remove it from the biller fields object
     *
     * @param BillerFields $billerFields The biller fields object
     * @param string       $billerName   The biller class
     * @throws BillerNotSupportedException
     * @throws \ProBillerNG\Logger\Exception
     * @return BillerFields
     */
    public static function create(BillerFields $billerFields, string $billerName): BillerFields
    {
        switch ($billerName) {
            case (RocketgateBiller::BILLER_NAME):
                return RocketgateBillerFields::create(
                    $billerFields->merchantId(),
                    $billerFields->merchantPassword(),
                    $billerFields->billerSiteId(),
                    $billerFields->sharedSecret(),
                    $billerFields->simplified3DS(),
                    $billerFields->merchantCustomerId()
                );
            default:
                throw new BillerNotSupportedException($billerName);
        }
    }
}
