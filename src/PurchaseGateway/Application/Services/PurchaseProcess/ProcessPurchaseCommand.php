<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess;

use ProBillerNG\Base\Application\Services\Command;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\Zip;

class ProcessPurchaseCommand extends Command
{
    /**
     * @var Site
     */
    private $site;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $email;

    /**
     * @var Zip
     */
    private $zip;

    /**
     * @var string
     */
    private $ccNum;

    /**
     * @var string
     */
    private $cvv;

    /**
     * @var string
     */
    private $expirationMonth;

    /**
     * @var string
     */
    private $expirationYear;

    /**
     * @var string
     */
    private $firstName;

    /**
     * @var string
     */
    private $lastName;

    /**
     * @var string
     */
    private $city;

    /**
     * @var string
     */
    private $state;

    /**
     * @var string
     */
    private $country;

    /**
     * @var string
     */
    private $phoneNumber;

    /**
     * @var string
     */
    private $address;

    /**
     * @var array
     */
    private $crossSales;

    /**
     * @var string
     */
    private $token;

    /**
     * @var string
     */
    private $sessionId;

    /**
     * @var string
     */
    private $requestUrl;

    /**
     * @var string
     */
    private $userAgent;

    /**
     * @var array
     */
    private $member;

    /**
     * @var array
     */
    private $payment;

    /**
     * @var string
     */
    private $lastFour;

    /**
     * @var string
     */
    private $paymentTemplateId;

    /**
     * @var string|null
     */
    private $ndWidgetData;

    /**
     * @var string|null
     */
    private $xForwardedFor;

    /**
     * @var string|null
     */
    private $paymentMethod;

    /**
     * @var string|null
     */
    private $routingNumber;

    /**
     * @var string|null
     */
    private $accountNumber;

    /**
     * @var bool|null
     */
    private $savingAccount;

    /**
     * @var string|null
     */
    private $socialSecurityLast4;

    /**
     * @var array|null
     */
    private $fraudHeaders;

    /**
     * ProcessPurchaseCommand constructor.
     *
     * @param Site        $site                Site
     * @param string      $username            Username
     * @param string      $password            Password
     * @param string      $email               Email
     * @param string      $ccNumber            CC number
     * @param string      $zip                 Zip
     * @param string      $cvv                 CVV
     * @param string      $expirationMonth     Expiration month
     * @param string      $expirationYear      Expiration year
     * @param string      $firstName           First name
     * @param string      $lastName            Last name
     * @param string      $address             Address
     * @param array       $crossSales          Cross Sales
     * @param string|null $city                City
     * @param string|null $state               State
     * @param string|null $country             Country
     * @param string|null $phoneNumber         Phone number
     * @param string      $token               Token
     * @param string      $sessionId           Decoded token
     * @param string      $requestUrl          Request Url
     * @param string|null $userAgent           User Agent
     * @param array       $member              Member
     * @param array       $payment             Payment
     * @param string|null $lastFour            LastFour
     * @param string|null $paymentTemplateId   PaymentTemplateId
     * @param string|null $ndWidgetData        NuDataWidgetData
     * @param string|null $xForwardedFor       X-Forwarded-For
     * @param string|null $paymentMethod       Payment Method
     * @param string|null $routingNumber       Routing Number
     * @param string|null $accountNumber       Account Number
     * @param bool|null   $savingAccount       Saving Account
     * @param string|null $socialSecurityLast4 Social Security Number Last 4 Digits
     * @param array|null  $fraudHeaders        Fraud Headers
     */
    public function __construct(
        Site $site,
        string $username,
        string $password,
        string $email,
        string $ccNumber,
        string $zip,
        string $cvv,
        string $expirationMonth,
        string $expirationYear,
        string $firstName,
        string $lastName,
        string $address,
        array $crossSales,
        ?string $city,
        ?string $state,
        ?string $country,
        ?string $phoneNumber,
        string $token,
        string $sessionId,
        string $requestUrl,
        ?string $userAgent,
        array $member = [],
        array $payment = [],
        ?string $lastFour = null,
        ?string $paymentTemplateId = null,
        ?string $ndWidgetData = null,
        ?string $xForwardedFor = null,
        ?string $paymentMethod = null,
        ?string $routingNumber = null,
        ?string $accountNumber = null,
        ?bool $savingAccount = null,
        ?string $socialSecurityLast4 = null,
        ?array $fraudHeaders = []
    ) {
        $this->site                = $site;
        $this->username            = $username;
        $this->password            = $password;
        $this->email               = $email;
        $this->ccNum               = $ccNumber;
        $this->zip                 = $zip;
        $this->cvv                 = $cvv;
        $this->expirationMonth     = $expirationMonth;
        $this->expirationYear      = $expirationYear;
        $this->firstName           = $firstName;
        $this->lastName            = $lastName;
        $this->address             = $address;
        $this->city                = $city;
        $this->state               = $state;
        $this->country             = $country;
        $this->phoneNumber         = $phoneNumber;
        $this->crossSales          = $crossSales;
        $this->token               = $token;
        $this->sessionId           = $sessionId;
        $this->requestUrl          = $requestUrl;
        $this->userAgent           = $userAgent;
        $this->member              = $this->initMember($member);
        $this->payment             = $payment;
        $this->lastFour            = $lastFour;
        $this->paymentTemplateId   = $paymentTemplateId;
        $this->ndWidgetData        = $ndWidgetData;
        $this->xForwardedFor       = $xForwardedFor;
        $this->paymentMethod       = $paymentMethod;
        $this->routingNumber       = $routingNumber;
        $this->accountNumber       = $accountNumber;
        $this->savingAccount       = $savingAccount;
        $this->socialSecurityLast4 = $socialSecurityLast4;
        $this->fraudHeaders        = $fraudHeaders;
    }

    /**
     * @return Site
     */
    public function site(): Site
    {
        return $this->site;
    }

    /**
     * @return string
     */
    public function username(): string
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function password(): string
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function email(): string
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function zip(): string
    {
        return $this->zip;
    }

    /**
     * @return string
     */
    public function ccNumber(): string
    {
        return $this->ccNum;
    }

    /**
     * @return string
     */
    public function cvv(): string
    {
        return $this->cvv;
    }

    /**
     * @return string
     */
    public function expirationMonth(): string
    {
        return $this->expirationMonth;
    }

    /**
     * @return string
     */
    public function expirationYear(): string
    {
        return $this->expirationYear;
    }

    /**
     * @return string
     */
    public function firstName(): string
    {
        return $this->firstName;
    }

    /**
     * @return string
     */
    public function lastName(): string
    {
        return $this->lastName;
    }

    /**
     * @return string|null
     */
    public function city(): ?string
    {
        return $this->city;
    }

    /**
     * @return string|null
     */
    public function state(): ?string
    {
        return $this->state;
    }

    /**
     * @return string|null
     */
    public function country(): ?string
    {
        return $this->country;
    }

    /**
     * @return string|null
     */
    public function phoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    /**
     * @return string
     */
    public function address(): ?string
    {
        return $this->address;
    }

    /**
     * @return array
     */
    public function crossSales(): array
    {
        return $this->crossSales;
    }

    /**
     * @return string
     */
    public function token(): string
    {
        return $this->token;
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
    public function requestUrl(): string
    {
        return $this->requestUrl;
    }

    /**
     * @return string
     */
    public function userAgent(): ?string
    {
        return $this->userAgent;
    }

    /**
     * @return array
     */
    public function member(): array
    {
        return $this->member;
    }

    /**
     * @return array
     */
    public function payment(): array
    {
        return $this->payment;
    }

    /**
     * @return string|null
     */
    public function paymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    /**
     * @return string
     */
    public function lastFour(): string
    {
        return $this->lastFour;
    }

    /**
     * @return string
     */
    public function paymentTemplateId(): string
    {
        return $this->paymentTemplateId;
    }

    /**
     * @return string|null
     */
    public function ndWidgetData(): ?string
    {
        return $this->ndWidgetData;
    }

    /**
     * @return string|null
     */
    public function xForwardedFor(): ?string
    {
        return $this->xForwardedFor;
    }

    /**
     * @return string|null
     */
    public function routingNumber(): ?string
    {
        return $this->routingNumber;
    }

    /**
     * @return string|null
     */
    public function accountNumber(): ?string
    {
        return $this->accountNumber;
    }

    /**
     * @return bool|null
     */
    public function savingAccount(): ?bool
    {
        return $this->savingAccount;
    }

    /**
     * @return string|null
     */
    public function socialSecurityLast4(): ?string
    {
        return $this->socialSecurityLast4;
    }

    /**
     * @return array
     */
    public function fraudHeaders(): array
    {
        return $this->fraudHeaders;
    }

    /**
     * @param [] $member Member Info from request
     *
     * @return array
     */
    private function initMember($member): array
    {
        if (isset($member['zipCode'])) {
            $member['zipCode'] = (string) $this->zip;
        }

        return $member;
    }
}
