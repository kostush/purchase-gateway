<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\Biller;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\QyssoBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\InvalidForceCascadeException;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerIdException;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Cascade\RetrieveCascadeTranslatingService;

class BillerFactoryService implements BillerFactory
{
    /**
     * @param string $billerName Biller Name
     * @return Biller
     * @throws Exception
     * @throws UnknownBillerNameException
     */
    public static function create(string $billerName): Biller
    {
        switch ($billerName) {
            case RocketgateBiller::BILLER_NAME:
                $biller = new RocketgateBiller();
                break;
            case NetbillingBiller::BILLER_NAME:
                $biller = new NetbillingBiller();
                break;
            case EpochBiller::BILLER_NAME:
                $biller = new EpochBiller();
                break;
            case QyssoBiller::BILLER_NAME:
                $biller = new QyssoBiller();
                break;
            default:
                throw new UnknownBillerNameException($billerName);
        }

        return $biller;
    }

    /**
     * @param string $forceCascade Force Cascade
     * @return Biller
     * @throws InvalidForceCascadeException
     * @throws Exception
     */
    public static function createFromForceCascade(string $forceCascade): Biller
    {
        switch ($forceCascade) {
            case RetrieveCascadeTranslatingService::TEST_ROCKETGATE:
                $biller = new RocketgateBiller();
                break;
            case RetrieveCascadeTranslatingService::TEST_NETBILLING:
                $biller = new NetbillingBiller();
                break;
            case RetrieveCascadeTranslatingService::TEST_EPOCH:
                $biller = new EpochBiller();
                break;
            case RetrieveCascadeTranslatingService::TEST_QYSSO:
                $biller = new QyssoBiller();
                break;
            default:
                throw new InvalidForceCascadeException($forceCascade);
        }

        return $biller;
    }

    /**
     * @param string $billerId Biller Id
     * @return Biller
     * @throws Exception
     * @throws UnknownBillerIdException
     */
    public static function createFromBillerId(string $billerId): Biller
    {
        switch ($billerId) {
            case RocketgateBiller::BILLER_ID:
                $biller = new RocketgateBiller();
                break;
            case NetbillingBiller::BILLER_ID:
                $biller = new NetbillingBiller();
                break;
            case EpochBiller::BILLER_ID:
                $biller = new EpochBiller();
                break;
            case QyssoBiller::BILLER_ID:
                $biller = new QyssoBiller();
                break;
            default:
                throw new UnknownBillerIdException($billerId);
        }

        return $biller;
    }
}
