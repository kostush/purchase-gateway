<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\Biller;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\BinRouting;
use ProBillerNG\PurchaseGateway\Domain\Model\ChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;

interface PerformTransactionAdapter extends TransactionAdapter
{
    /**
     * @param SiteId            $siteId            Site Id
     * @param Biller            $biller            Biller
     * @param CurrencyCode      $currencyCode      Currency code
     * @param UserInfo          $userInfo          User info
     * @param ChargeInformation $chargeInformation Charge information
     * @param PaymentInfo       $paymentInfo       Payment info
     * @param BillerMapping     $billerMapping     Biller mapping
     * @param SessionId         $sessionId         Session Id
     * @param BinRouting|null   $binRouting        Bin routing
     * @param bool              $useThreeD         Perform transaction using 3DS
     * @param string            $returnUrl         The simplified 3ds return url
     * @param bool              $isNSFSupported    Flag to show if NSF is supported or not
     *
     * @return Transaction
     */
    public function performTransaction(
        SiteId $siteId,
        Biller $biller,
        CurrencyCode $currencyCode,
        UserInfo $userInfo,
        ChargeInformation $chargeInformation,
        PaymentInfo $paymentInfo,
        BillerMapping $billerMapping,
        SessionId $sessionId,
        ?BinRouting $binRouting,
        bool $useThreeD,
        ?string $returnUrl = null,
        bool $isNSFSupported = false
    ): Transaction;
}
