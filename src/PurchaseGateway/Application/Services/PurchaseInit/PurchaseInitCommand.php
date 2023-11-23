<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit;

use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\Base\Application\Services\Command;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidAmountException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidDaysException;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;

class PurchaseInitCommand extends Command
{
    /**
     * @var Site
     */
    private $site;

    /**
     * @var float|null
     */
    private $amount;

    /**
     * @var int
     */
    private $initialDays;

    /**
     * @var int
     */
    private $rebillDays;

    /**
     * @var float
     */
    private $rebillAmount;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var string
     */
    private $bundleId;

    /**
     * @var string
     */
    private $addOnId;

    /**
     * @var string
     */
    private $clientIp;

    /**
     * @var string
     */
    private $paymentType;

    /**
     * @var string
     */
    private $clientCountryCode;

    /**
     * @var string
     */
    private $sessionId;
    /**
     * @var string
     */
    private $atlasData;

    /**
     * @var string
     */
    private $atlasCode;

    /**
     * @var int
     */
    private $publicKeyIndex;

    /**
     * @var array|null
     */
    private $tax;

    /**
     * @var array
     */
    private $crossSales;

    /**
     * @var bool
     */
    private $isTrial;

    /**
     * @var string|null
     */
    private $memberId;

    /**
     * @var string|null
     */
    private $subscriptionId;

    /**
     * @var string|null
     */
    private $entrySiteId;

    /**
     * @var string|null
     */
    private $forceCascade;

    /**
     * @var string|null
     */
    private $paymentMethod;

    /**
     * @var string|null
     */
    private $trafficSource;

    /**
     * @var string|null
     */
    private $redirectUrl;

    /** @var string|null */
    private $postbackUrl;

    /** @var array|null */
    private $fraudHeaders;

    protected const MIN_DAYS = 0;
    protected const MAX_DAYS = 10000;
    /**
     * @var bool
     */
    private $skipVoid;

    /**
     * PurchaseInitCommand constructor.
     *
     * @param Site        $site              Site
     * @param mixed       $amount            Amount
     * @param int         $initialDays       Initial Days
     * @param int         $rebillDays        Rebill Days
     * @param float       $rebillAmount      Rebill Amount
     * @param string      $currency          Currency
     * @param string      $bundleId          Bundle Id
     * @param string      $addOnId           Addon Id
     * @param string      $clientIp          Client Ip
     * @param string      $paymentType       Payment Type
     * @param string      $clientCountryCode Client Country code
     * @param string      $sessionId         Session Id
     * @param string|null $atlasCode         Atlas Code
     * @param string|null $atlasData         Atlas Data
     * @param int         $publicKeyIndex    Public Key Index
     * @param array|null  $tax               Tax
     * @param array       $crossSales        CrossSales
     * @param bool        $isTrial           Is trial
     * @param string|null $memberId          Member Id
     * @param string|null $subscriptionId    Subscription Id
     * @param string|null $entrySiteId       Entry Site Id
     * @param string|null $forceCascade      Force Cascade
     * @param string|null $paymentMethod     Payment Method Type
     * @param string|null $trafficSource     Traffic Source
     * @param string|null $redirectUrl       Redirect url for 3DS
     * @param string|null $postbackUrl       Postback url
     * @param array|null  $fraudHeaders      The headers required by fraud
     * @param bool        $skipVoid          Skip void
     * @throws InvalidAmountException
     * @throws InvalidDaysException
     * @throws LoggerException
     */
    public function __construct(
        Site $site,
        $amount,
        int $initialDays,
        int $rebillDays,
        float $rebillAmount,
        string $currency,
        string $bundleId,
        string $addOnId,
        string $clientIp,
        string $paymentType,
        string $clientCountryCode,
        string $sessionId,
        ?string $atlasCode,
        ?string $atlasData,
        int $publicKeyIndex,
        ?array $tax,
        array $crossSales,
        bool $isTrial,
        ?string $memberId,
        ?string $subscriptionId,
        ?string $entrySiteId,
        ?string $forceCascade,
        ?string $paymentMethod,
        ?string $trafficSource,
        ?string $redirectUrl,
        ?string $postbackUrl,
        ?array $fraudHeaders = [],
        bool $skipVoid = false
    ) {
        $this->initAmount($amount);
        $this->site              = $site;
        $this->initialDays       = $this->sanitizeDays($initialDays);
        $this->rebillDays        = $rebillDays ? $this->sanitizeDays($rebillDays) : $rebillDays;
        $this->rebillAmount      = $rebillAmount;
        $this->currency          = $currency;
        $this->bundleId          = $bundleId;
        $this->addOnId           = $addOnId;
        $this->clientIp          = $clientIp;
        $this->paymentType       = $paymentType;
        $this->clientCountryCode = $clientCountryCode;
        $this->sessionId         = $sessionId;
        $this->atlasData         = $atlasData;
        $this->atlasCode         = $atlasCode;
        $this->publicKeyIndex    = $publicKeyIndex;
        $this->tax               = $tax;
        $this->crossSales        = $crossSales;
        $this->isTrial           = $isTrial;
        $this->memberId          = $memberId;
        $this->subscriptionId    = $subscriptionId;
        $this->entrySiteId       = $entrySiteId;
        $this->forceCascade      = $forceCascade;
        $this->paymentMethod     = $paymentMethod;
        $this->trafficSource     = $trafficSource;
        $this->redirectUrl       = $redirectUrl;
        $this->postbackUrl       = $postbackUrl;
        $this->fraudHeaders      = $fraudHeaders;
        $this->skipVoid          = $skipVoid;
    }

    /**
     * @return Site
     */
    public function site(): Site
    {
        return $this->site;
    }

    /**
     * @return float|null
     */
    public function amount(): ?float
    {
        return $this->amount;
    }

    /**
     * @return int
     */
    public function initialDays(): int
    {
        return $this->initialDays;
    }

    /**
     * @return int
     */
    public function rebillDays(): int
    {
        return $this->rebillDays;
    }

    /**
     * @return float
     */
    public function rebillAmount(): float
    {
        return $this->rebillAmount;
    }

    /**
     * @return string
     */
    public function currency(): string
    {
        return $this->currency;
    }

    /**
     * @return string
     */
    public function bundleId(): string
    {
        return $this->bundleId;
    }

    /**
     * @return string
     */
    public function addOnId(): string
    {
        return $this->addOnId;
    }

    /**
     * @return string
     */
    public function clientIp(): string
    {
        return $this->clientIp;
    }

    /**
     * @return string
     */
    public function paymentType(): string
    {
        return $this->paymentType;
    }

    /**
     * @return string
     */
    public function clientCountryCode(): string
    {
        return $this->clientCountryCode;
    }

    /**
     * @return string
     */
    public function sessionId(): string
    {
        return $this->sessionId;
    }

    /**
     * @return string
     */
    public function atlasCode(): ?string
    {
        return $this->atlasCode;
    }

    /**
     * @return string
     */
    public function atlasData(): ?string
    {
        return $this->atlasData;
    }

    /**
     * @return int
     */
    public function publicKeyIndex(): int
    {
        return $this->publicKeyIndex;
    }

    /**
     * @return array
     */
    public function tax(): array
    {
        return $this->tax;
    }

    /**
     * @return array
     */
    public function crossSales(): array
    {
        return $this->crossSales;
    }

    /**
     * @return bool
     */
    public function isTrial(): bool
    {
        return $this->isTrial;
    }

    /**
     * @return string|null
     */
    public function memberId(): ?string
    {
        return $this->memberId;
    }

    /**
     * @return string|null
     */
    public function subscriptionId(): ?string
    {
        return $this->subscriptionId;
    }

    /**
     * @return string|null
     */
    public function entrySiteId(): ?string
    {
        return $this->entrySiteId;
    }

    /**
     * @return string|null
     */
    public function forceCascade(): ?string
    {
        return $this->forceCascade;
    }

    /**
     * @return bool
     */
    public function skipVoid(): bool
    {
        return $this->skipVoid;
    }

    /**
     * @return string|null
     */
    public function paymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    /**
     * @return string|null
     */
    public function trafficSource(): ?string
    {
        return $this->trafficSource;
    }

    /**
     * @return string|null
     */
    public function redirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    /**
     * @return string|null
     */
    public function postbackUrl(): ?string
    {
        return $this->postbackUrl;
    }

    /**
     * @return array
     */
    public function fraudHeaders(): array
    {
        return $this->fraudHeaders;
    }

    /**
     * @param mixed $amount Amount
     *
     * @return void
     * @throws LoggerException
     * @throws InvalidAmountException
     */
    private function initAmount($amount): void
    {
        // This validation before the actual Amount object is to guarantee that whenever we get to the point to create
        // Domain\Model\ChargeInformation we have a float value.
        if (!$this->isValidFloat($amount)) {
            throw new InvalidAmountException('amount');
        }

        // At this point we can cast, cause an amount was given and is a valid float.
        $this->amount = (float) $amount;
    }

    /**
     * @param mixed $days Number of days to be validated (in this case initialDays or rebilldays)
     *
     * @return int|null
     * @throws InvalidDaysException
     * @throws LoggerException
     */
    private function sanitizeDays($days): ?int
    {
        $days = filter_var($days, FILTER_VALIDATE_INT,
            $options = array(
                'options' => array(
                    'min_range' => self::MIN_DAYS,
                    'max_range' => self::MAX_DAYS
                )
            ));

        if (!is_integer($days)){
            throw new InvalidDaysException();
        }

        return $days;
    }
}
