<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\Biller;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\ChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\TaxInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\ThirdPartyTransaction;

interface PerformThirdPartyTransactionAdapter extends TransactionAdapter
{
    /**
     * @param Site                $site              Site Id.
     * @param array               $crossSaleSites    Cross sales sites.
     * @param Biller              $biller            Biller.
     * @param CurrencyCode        $currencyCode      Currency code.
     * @param UserInfo            $userInfo          User info.
     * @param ChargeInformation   $chargeInformation Charge information.
     * @param PaymentInfo         $paymentInfo       Payment info.
     * @param BillerMapping       $billerMapping     Biller mapping.
     * @param SessionId           $sessionId         Session id.
     * @param string              $redirectUrl       Redirect url.
     * @param TaxInformation|null $taxInformation    Tax information.
     * @param array|null          $crossSales        Cross sales list.
     * @param string|null         $paymentMethod     Payment method.
     * @param string|null         $billerMemberId    Biller member id.
     * @return ThirdPartyTransaction
     */
    public function performTransaction(
        Site $site,
        array $crossSaleSites,
        Biller $biller,
        CurrencyCode $currencyCode,
        UserInfo $userInfo,
        ChargeInformation $chargeInformation,
        PaymentInfo $paymentInfo,
        BillerMapping $billerMapping,
        SessionId $sessionId,
        string $redirectUrl,
        ?TaxInformation $taxInformation,
        ?array $crossSales,
        ?string $paymentMethod,
        ?string $billerMemberId
    ): ThirdPartyTransaction;
}
