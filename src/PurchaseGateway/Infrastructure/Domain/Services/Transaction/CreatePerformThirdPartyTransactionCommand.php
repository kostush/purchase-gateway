<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use ProBillerNG\CircuitBreaker\ExternalCommand;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\Biller;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\ChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\TaxInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Domain\Services\PerformThirdPartyTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\UnableToProcessTransactionException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\ThirdPartyTransaction;

class CreatePerformThirdPartyTransactionCommand extends ExternalCommand
{
    /** @var PerformThirdPartyTransactionAdapter */
    private $adapter;

    /** @var Site */
    private $site;

    /** @var array */
    private $crossSaleSites;

    /** @var Biller */
    private $biller;

    /** @var CurrencyCode */
    private $currencyCode;

    /** @var UserInfo */
    private $userInfo;

    /** @var ChargeInformation */
    private $chargeInformation;

    /** @var PaymentInfo */
    private $paymentInfo;

    /** @var BillerMapping */
    private $billerMapping;

    /** @var SessionId */
    private $sessionId;

    /** @var TaxInformation|null */
    private $taxInformation;

    /** @var string */
    private $redirectUrl;

    /**
     * @var array|null
     */
    private $crossSales;

    /**
     * @var string|null
     */
    private $paymentMethod;

    /**
     * @var string|null
     */
    private $billerMemberId;

    /**
     * CreatePerformThirdPartyTransactionCommand constructor.
     * @param PerformThirdPartyTransactionAdapter $adapter           Adapter.
     * @param Site                                $site              Site.
     * @param array                               $crossSaleSites    Cross sales sites.
     * @param Biller                              $biller            Biller.
     * @param CurrencyCode                        $currencyCode      Currency code.
     * @param UserInfo                            $userInfo          User info.
     * @param ChargeInformation                   $chargeInformation Charge information.
     * @param PaymentInfo                         $paymentInfo       Payment info.
     * @param BillerMapping                       $billerMapping     Biller mapping.
     * @param SessionId                           $sessionId         Session id.
     * @param string                              $redirectUrl       Redirect url.
     * @param TaxInformation|null                 $taxInformation    Tax information.
     * @param array                               $crossSales        Cross sales.
     * @param string|null                         $paymentMethod     Payment method.
     * @param string|null                         $billerMemberId    Biller member id.
     */
    public function __construct(
        PerformThirdPartyTransactionAdapter $adapter,
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
    ) {
        $this->adapter           = $adapter;
        $this->site              = $site;
        $this->crossSaleSites    = $crossSaleSites;
        $this->biller            = $biller;
        $this->currencyCode      = $currencyCode;
        $this->userInfo          = $userInfo;
        $this->chargeInformation = $chargeInformation;
        $this->paymentInfo       = $paymentInfo;
        $this->billerMapping     = $billerMapping;
        $this->sessionId         = $sessionId;
        $this->redirectUrl       = $redirectUrl;
        $this->taxInformation    = $taxInformation;
        $this->crossSales        = $crossSales;
        $this->paymentMethod     = $paymentMethod;
        $this->billerMemberId    = $billerMemberId;
    }

    /**
     * @return ThirdPartyTransaction
     */
    protected function run(): ThirdPartyTransaction
    {
        return $this->adapter->performTransaction(
            $this->site,
            $this->crossSaleSites,
            $this->biller,
            $this->currencyCode,
            $this->userInfo,
            $this->chargeInformation,
            $this->paymentInfo,
            $this->billerMapping,
            $this->sessionId,
            $this->redirectUrl,
            $this->taxInformation,
            $this->crossSales,
            $this->paymentMethod,
            $this->billerMemberId
        );
    }

    /**
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Exception
     */
    protected function getFallback(): void
    {
        Log::error('Error contacting Transaction Service');

        throw new UnableToProcessTransactionException();
    }
}
