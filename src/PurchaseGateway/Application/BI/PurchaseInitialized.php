<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\BI;

class PurchaseInitialized extends PurchaseEvent
{
    const TYPE = 'Purchase_Initialized';

    const LATEST_VERSION = 7;

    /** @var string */
    protected $bundleId;

    /** @var array */
    protected $addOns;

    /** @var string */
    protected $clientIp;

    /** @var float */
    protected $amount;

    /** @var integer */
    protected $initialDays;

    /** @var float */
    protected $rebillAmount;

    /** @var integer */
    protected $rebillDays;

    /** @var string */
    protected $currency;

    /** @var string */
    protected $paymentType;

    /** @var string */
    protected $clientCountryCode;

    /** @var array|null */
    protected $tax;

    /** @var array */
    protected $availableCrossSells;

    /** @var string */
    protected $taxAmountInformed;

    /** @var string|null */
    protected $memberId;

    /** @var string|null */
    protected $subscriptionId;

    /** @var string|null */
    protected $entrySiteId;

    /** @var array|null */
    protected $paymentTemplates;

    /** @var  array|null */
    protected $atlasCode;

    /** @var array|null */
    protected $fraudRecommendation;

    /** @var array|null */
    protected $fraudRecommendationCollection;

    /** @var array|null */
    protected $threeD;

    /**
     * @var string|null
     */
    private $paymentMethod;

    /**
     * @var string|null
     */
    private $trafficSource;

    /** @var array|null */
    private $gatewayServiceFlags;

    /** @var array */
    private $selectedCascadeInfo;

    /**
     * PurchaseInitialized constructor.
     * @param string      $sessionId                     Session Id.
     * @param array       $mainPurchaseItem              The main purchase item
     * @param array       $availableCrossSells           Available Cross Sells
     * @param string      $clientIp                      Client IP.
     * @param string      $currency                      Currency
     * @param string      $paymentType                   Payment Type.
     * @param string      $clientCountryCode             Client Country code.
     * @param string|null $memberId                      Member Id
     * @param string|null $entrySiteId                   Entry Site Id
     * @param array|null  $paymentTemplates              Payment Templates
     * @param array|null  $atlasCode                     Atlas Code Decoded
     * @param array|null  $fraudRecommendation           Fraud recommendation
     * @param array|null  $threeD                        ThreeD
     * @param string|null $paymentMethod                 Payment method
     * @param string|null $trafficSource                 Traffic source
     * @param array|null  $fraudRecommendationCollection Fraud Recommendation
     * @param array|null  $gatewayServiceFlags           Gateway Service Flag for Netbilling
     * @param array|null  $selectedCascadeInfo           Selected Casecade Info
     */
    public function __construct(
        string $sessionId,
        array $mainPurchaseItem,
        array $availableCrossSells,
        string $clientIp,
        string $currency,
        string $paymentType,
        string $clientCountryCode,
        ?string $memberId,
        ?string $entrySiteId,
        ?array $paymentTemplates,
        ?array $atlasCode,
        ?array $fraudRecommendation,
        ?array $threeD,
        ?string $paymentMethod,
        ?string $trafficSource,
        ?array $fraudRecommendationCollection,
        ?array $gatewayServiceFlags,
        ?array $selectedCascadeInfo
    ) {
        parent::__construct(self::TYPE, $sessionId, $mainPurchaseItem['siteId'], new \DateTimeImmutable());

        $this->bundleId                      = $mainPurchaseItem['bundleId'];
        $this->addOns                        = [$mainPurchaseItem['addonId']];
        $this->clientIp                      = $clientIp;
        $this->amount                        = $mainPurchaseItem['initialAmount'];
        $this->initialDays                   = $mainPurchaseItem['initialDays'];
        $this->rebillAmount                  = $mainPurchaseItem['rebillAmount'];
        $this->rebillDays                    = $mainPurchaseItem['rebillDays'];
        $this->currency                      = $currency;
        $this->paymentType                   = $paymentType;
        $this->clientCountryCode             = $clientCountryCode;
        $this->tax                           = $mainPurchaseItem['tax'];
        $this->availableCrossSells           = $availableCrossSells;
        $this->taxAmountInformed             = isset($mainPurchaseItem['tax']['taxApplicationId']) ? 'Yes' : 'No';
        $this->memberId                      = $memberId;
        $this->subscriptionId                = $mainPurchaseItem['subscriptionId'] ?? null;
        $this->entrySiteId                   = $entrySiteId;
        $this->paymentTemplates              = $paymentTemplates;
        $this->atlasCode                     = $atlasCode;
        $this->fraudRecommendation           = $fraudRecommendation;
        $this->fraudRecommendationCollection = $fraudRecommendationCollection;
        $this->threeD                        = $threeD;
        $this->paymentMethod                 = $paymentMethod;
        $this->trafficSource                 = $trafficSource;
        $this->gatewayServiceFlags           = $gatewayServiceFlags;
        $this->selectedCascadeInfo           = $selectedCascadeInfo;

        $this->setValue($this->toArray());
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'type'                          => self::TYPE,
            'version'                       => $this->version,
            'timestamp'                     => $this->timestamp,
            'sessionId'                     => $this->sessionId,
            'siteId'                        => $this->siteId,
            'bundleId'                      => $this->bundleId,
            'addOns'                        => $this->addOns,
            'clientIp'                      => $this->clientIp,
            'amount'                        => $this->amount,
            'initialDays'                   => $this->initialDays,
            'rebillAmount'                  => $this->rebillAmount,
            'rebillDays'                    => $this->rebillDays,
            'currency'                      => $this->currency,
            'paymentType'                   => $this->paymentType,
            'clientCountryCode'             => $this->clientCountryCode,
            'tax'                           => $this->tax,
            'availableCrossSells'           => $this->availableCrossSells,
            'taxAmountInformed'             => $this->taxAmountInformed,
            'memberId'                      => $this->memberId,
            'subscriptionId'                => $this->subscriptionId,
            'entrySiteId'                   => $this->entrySiteId,
            'paymentTemplateInfo'           => $this->paymentTemplates,
            'atlasCode'                     => $this->atlasCode,
            'fraudRecommendation'           => $this->fraudRecommendation,
            'threeD'                        => $this->threeD,
            'paymentMethod'                 => $this->paymentMethod,
            'trafficSource'                 => $this->trafficSource,
            'fraudRecommendationCollection' => $this->fraudRecommendationCollection,
            'gatewayServiceFlags'           => $this->gatewayServiceFlags,
            'selectedCascadeInfo'           => $this->selectedCascadeInfo
        ];
    }
}
