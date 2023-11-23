<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\BI;

use Illuminate\Support\Carbon;
use ProBillerNG\Base\Application\Services\BI\BaseEvent;
use ProBillerNG\PurchaseGateway\Domain\Model\Ip;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\ChequePaymentInfo;

class FraudPurchaseVelocity extends BaseEvent
{
    const TYPE = 'Fraud_Purchase_Velocity';

    const LATEST_VERSION = 3;

    const EMAIL_SEPARATOR = '@';

    /**
     * @var string
     */
    protected $timestamp;

    /**
     * @var int
     */
    protected $version;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var array
     */
    protected $memberInfo;

    /**
     * @var array
     */
    protected $payment;

    /**
     * @var bool|null
     */
    protected $isSafeSelectedPaymentTemplate = null;

    /**
     * @var float
     */
    protected $mainPurchaseAmount;

    /**
     * @var float
     */
    protected $crossSaleAmount;

    /**
     * @var float
     */
    protected $totalChargedAmount;

    /**
     * @var int
     */
    protected $countSubmitAttempt;

    /**
     * @var string
     */
    protected $billerName;

    /**
     * @var string
     */
    protected $siteId;

    /**
     * @var string
     */
    protected $businessGroupId;

    /**
     * FraudPurchaseVelocity constructor.
     * @param           $siteId                        Site id.
     * @param           $businessGroupId               Bussiness group id.
     * @param           $status                        Status.
     * @param           $memberInfo                    Member info.
     * @param           $payment                       Payment.
     * @param           $mainPurchaseAmount            Main purchase amount.
     * @param           $crossSaleAmount               Cross sale amount.
     * @param           $totalChargedAmount            Total charge amount.
     * @param           $countSubmitAttempt            Count submit attempt.
     * @param           $billerName                    Biller name.
     * @param bool|null $isSafeSelectedPaymentTemplate Is safe selected payment template.
     */
    public function __construct(
        $siteId,
        $businessGroupId,
        $status,
        $memberInfo,
        $payment,
        $mainPurchaseAmount,
        $crossSaleAmount,
        $totalChargedAmount,
        $countSubmitAttempt,
        $billerName,
        ?bool $isSafeSelectedPaymentTemplate = null
    ) {
        parent::__construct(static::TYPE);
        $this->version                       = static::LATEST_VERSION;
        $this->timestamp                     = Carbon::now()->toISOString();
        $this->siteId                        = $siteId;
        $this->businessGroupId               = $businessGroupId;
        $this->status                        = $status;
        $this->memberInfo                    = $memberInfo;
        $this->payment                       = $payment;
        $this->mainPurchaseAmount            = $mainPurchaseAmount;
        $this->crossSaleAmount               = $crossSaleAmount;
        $this->totalChargedAmount            = $totalChargedAmount;
        $this->countSubmitAttempt            = $countSubmitAttempt;
        $this->billerName                    = $billerName;
        $this->isSafeSelectedPaymentTemplate = $isSafeSelectedPaymentTemplate;

        $this->setValue($this->toArray());
    }

    /**
     * @param PurchaseProcessed $purchaseProcessed             PurchaseProcessed
     * @param Ip                $ipAddress                     Ip
     * @param Site              $site                          Site
     * @param PaymentInfo       $paymentInfo                   PaymentInfo
     * @param bool|null         $isSafeSelectedPaymentTemplate Is safe selected payment template.
     * @return FraudPurchaseVelocity
     */
    public static function createFromPurchaseProcessed(
        PurchaseProcessed $purchaseProcessed,
        Ip $ipAddress,
        Site $site,
        PaymentInfo $paymentInfo,
        ?bool $isSafeSelectedPaymentTemplate
    ): self {
        $purchaseProcessedAsArray = $purchaseProcessed->toArray();

        $memberInfo = [
            'clientIp'    => $ipAddress->ip(),
            'email'       => $purchaseProcessedAsArray['memberInfo']['email'],
            'domain'      => self::extractDomainFromEmail(
                $purchaseProcessedAsArray['memberInfo']['email']
            ),
            'username'    => $purchaseProcessedAsArray['memberInfo']['username'],
            'firstName'   => $purchaseProcessedAsArray['memberInfo']['firstName'],
            'lastName'    => $purchaseProcessedAsArray['memberInfo']['lastName'],
            'countryCode' => $purchaseProcessedAsArray['memberInfo']['countryCode'],
            'zipCode'     => $purchaseProcessedAsArray['memberInfo']['zipCode'],
            'address'     => $purchaseProcessedAsArray['memberInfo']['address'],
            'city'        => $purchaseProcessedAsArray['memberInfo']['city'],
        ];

        $payment                 = self::getPaymentInfoFromPurchaseProcessed($purchaseProcessedAsArray, $paymentInfo);
        $crossSaleApprovedAmount = self::sumApprovedCrossSellsAmount($purchaseProcessedAsArray['selectedCrossSells']);

        $totalChargedAmount = array_sum(
            [
                self::initialAmountFromApproved($purchaseProcessedAsArray),
                $crossSaleApprovedAmount
            ]
        );

        return new static(
            (string) $site->siteId(),
            (string) $site->businessGroupId(),
            $purchaseProcessedAsArray['status'],
            $memberInfo,
            $payment,
            $purchaseProcessedAsArray['initialAmount'],
            $crossSaleApprovedAmount,
            $totalChargedAmount,
            data_get($purchaseProcessedAsArray, 'cascadeAttemptedTransactions.0.submitAttempt', 1),
            data_get($purchaseProcessedAsArray, 'cascadeAttemptedTransactions.0.billerName', ''),
            $isSafeSelectedPaymentTemplate
        );
    }

    /**
     * @param string $email Email
     * @return string
     */
    private static function extractDomainFromEmail(string $email): string
    {
        $emailAsArray = explode(self::EMAIL_SEPARATOR, $email);

        return array_pop($emailAsArray);
    }

    /**
     * @param array $item Item
     * @return float
     */
    private static function initialAmountFromApproved(array $item): float
    {
        $status        = $item['status'] ?? '';
        $initialAmount = $item['initialAmount'] ?? 0;
        if ($status === Transaction::STATUS_APPROVED) {
            return $initialAmount;
        }
        return 0;
    }

    /**
     * @param array $selectedCrossSales Cross sales
     * @return float
     */
    private static function sumApprovedCrossSellsAmount(array $selectedCrossSales): float
    {
        return array_sum(array_map([self::class, 'initialAmountFromApproved'], $selectedCrossSales));
    }

    /**
     * @return bool
     */
    public function isApproved(): bool
    {
        return $this->status === Transaction::STATUS_APPROVED;
    }

    /**
     * @return string
     */
    public function siteId(): string
    {
        return $this->siteId;
    }

    /**
     * @param array         $purchaseProcessedAsArray
     * @param PaymentInfo   $paymentInfo
     *
     * @return array
     */
    private static function getPaymentInfoFromPurchaseProcessed(array $purchaseProcessedAsArray, PaymentInfo $paymentInfo): array
    {
        if ($paymentInfo->paymentType() === ChequePaymentInfo::PAYMENT_TYPE)
        {
            return self::getChequePaymentInfoFromPurchaseProcessed($purchaseProcessedAsArray);
        }

        return self::getCCPaymentInfoFromPurchaseProcessed($purchaseProcessedAsArray);
    }

    /**
     * @param array $purchaseProcessedAsArray
     *
     * @return array
     */
    private static function getCCPaymentInfoFromPurchaseProcessed(array $purchaseProcessedAsArray): array
    {
        $first6 = $purchaseProcessedAsArray['payment']['first6'] ?? null;
        $last4  = $purchaseProcessedAsArray['payment']['last4'] ?? null;
        $card   = '';

        if ($first6 && $last4) {
            $card = $first6 . $last4;
        }

        $payment = [
            'first6' => $first6,
            'last4'  => $last4,
            'card'   => $card,
        ];

        return $payment;
    }

    /**
     * @param array $purchaseProcessedAsArray
     *
     * @return array
     */
    private static function getChequePaymentInfoFromPurchaseProcessed(array $puchaseProcessedAsArray): array
    {
        $routingNumber = $puchaseProcessedAsArray['payment']['routingNumber'] ?? null;

        $payment = [
            'routingNumber' => $routingNumber
        ];

        return $payment;
    }


    /**
     * @return array
     */
    public function toArray(): array
    {
        $data = [
            'type'                            => $this->getType(),
            'version'                         => $this->version,
            'timestamp'                       => $this->timestamp,
            'siteId'                          => $this->siteId,
            'businessGroupId'                 => $this->businessGroupId,
            'status'                          => $this->status,
            'memberInfo'                      => $this->memberInfo,
            'payment'                         => $this->payment,
            'mainPurchaseAmount'              => $this->mainPurchaseAmount,
            'crossSaleAmount'                 => $this->crossSaleAmount,
            'totalChargedAmount'              => $this->totalChargedAmount,
            'countSubmitAttempt'              => $this->countSubmitAttempt,
            'billerName'                      => $this->billerName,
        ];

        if ($this->isSafeSelectedPaymentTemplate !== null) {
            $data['bypassPaymentTemplateValidation'] = $this->isSafeSelectedPaymentTemplate;
        }

        return $data;
    }
}
