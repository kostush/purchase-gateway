<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\Biller;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\QyssoBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\InvalidBillerFieldsDataException;

class BillerFieldsFactoryService implements BillerFieldsFactory
{
    /**
     * @param Biller $biller           Biller
     * @param array  $billerFieldsData Biller Fields Data
     * @return BillerFields
     * @throws \ProBillerNG\Logger\Exception
     * @throws Exception
     */
    public static function create(Biller $biller, array $billerFieldsData): BillerFields
    {
        $billerFields = null;

        try {
            switch ($biller->name()) {
                case NetbillingBiller::BILLER_NAME:
                    $billerFields = (new NetbillingBillerFieldsDataAdapter())->convert($billerFieldsData);
                    break;

                case RocketgateBiller::BILLER_NAME:
                    $billerFields = (new RocketgateBillerFieldsDataAdapter())->convert($billerFieldsData);
                    break;

                case EpochBiller::BILLER_NAME:
                    $billerFields = (new EpochBillerFieldsDataAdapter())->convert($billerFieldsData);
                    break;

                case QyssoBiller::BILLER_NAME:
                    $billerFields = (new QyssoBillerFieldsDataAdapter())->convert($billerFieldsData);
                    break;
            }
        } catch (Exception $e) {
            Log::error('Error occurred while creating biller fields from array');
            throw $e;
        }

        if ($billerFields === null) {
            throw new InvalidBillerFieldsDataException();
        }

        return $billerFields;
    }
}
