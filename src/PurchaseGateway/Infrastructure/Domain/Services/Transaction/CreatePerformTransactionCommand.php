<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use ProBillerNG\CircuitBreaker\ExternalCommand;
use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
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
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\UnableToProcessTransactionException;
use ProbillerNG\TransactionServiceClient\ApiException;

class CreatePerformTransactionCommand extends ExternalCommand
{
    /** @var TransactionAdapter */
    private $adapter;

    /** @var SiteId */
    private $siteId;

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

    /** @var BinRouting */
    private $binRouting;

    /** @var bool */
    private $useThreeD;

    /** @var string */
    private $returnUrl;

    /** @var bool */
    private $isNSFSupported;

    /**
     * CreatePerformTransactionCommand constructor.
     *
     * @param TransactionAdapter $adapter           Adapter
     * @param SiteId             $siteId            Site Id
     * @param Biller             $biller            Biller
     * @param CurrencyCode       $currencyCode      Currency code
     * @param UserInfo           $userInfo          User info
     * @param ChargeInformation  $chargeInformation Charge information
     * @param PaymentInfo        $paymentInfo       Payment info
     * @param BillerMapping      $billerMapping     Biller mapping
     * @param SessionId          $sessionId         Session Id
     * @param BinRouting|null    $binRouting        Bin routing
     * @param bool               $useThreeD         Perform transaction using 3DS
     * @param string|null        $returnUrl         Return URL
     * @param bool               $isNSFSupported    Flag to show if NSF is supported or not
     */
    public function __construct(
        TransactionAdapter $adapter,
        SiteId $siteId,
        Biller $biller,
        CurrencyCode $currencyCode,
        UserInfo $userInfo,
        ChargeInformation $chargeInformation,
        PaymentInfo $paymentInfo,
        BillerMapping $billerMapping,
        SessionId $sessionId,
        ?BinRouting $binRouting = null,
        bool $useThreeD = false,
        ?string $returnUrl = null,
        bool $isNSFSupported = false
    ) {
        $this->adapter           = $adapter;
        $this->siteId            = $siteId;
        $this->biller            = $biller;
        $this->currencyCode      = $currencyCode;
        $this->userInfo          = $userInfo;
        $this->chargeInformation = $chargeInformation;
        $this->paymentInfo       = $paymentInfo;
        $this->billerMapping     = $billerMapping;
        $this->sessionId         = $sessionId;
        $this->binRouting        = $binRouting;
        $this->useThreeD         = $useThreeD;
        $this->returnUrl         = $returnUrl;
        $this->isNSFSupported    = $isNSFSupported;
    }

    /**
     * @return Transaction
     * @throws Exception
     * @throws Exceptions\BillerNotSupportedException
     * @throws Exceptions\InvalidResponseException
     * @throws ApiException
     */
    protected function run(): Transaction
    {
        if ($this->adapter instanceof NewChequePerformTransactionAdapter) {
            return $this->adapter->performTransaction(
                $this->siteId,
                $this->biller,
                $this->currencyCode,
                $this->userInfo,
                $this->chargeInformation,
                $this->paymentInfo,
                $this->billerMapping,
                $this->sessionId
            );
        }

        return $this->adapter->performTransaction(
            $this->siteId,
            $this->biller,
            $this->currencyCode,
            $this->userInfo,
            $this->chargeInformation,
            $this->paymentInfo,
            $this->billerMapping,
            $this->sessionId,
            $this->binRouting,
            $this->useThreeD,
            $this->returnUrl,
            $this->isNSFSupported
        );
    }

    /**
     * @return void
     * @throws Exception
     * @throws \Exception
     */
    protected function getFallback(): void
    {
        Log::error('Error contacting Transaction Service');

        throw new UnableToProcessTransactionException();
    }
}
