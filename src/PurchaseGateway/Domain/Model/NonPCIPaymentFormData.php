<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

class NonPCIPaymentFormData
{
    /**
     * @var Bin|null
     */
    private $bin;

    /**
     * @var LastFour|null
     */
    private $lastFour;

    /**
     * @var FirstName|null
     */
    private $firstName;

    /**
     * @var LastName|null
     */
    private $lastName;

    /**
     * @var Email|null
     */
    private $email;

    /**
     * @var string|null
     */
    private $street;

    /**
     * @var string|null
     */
    private $city;

    /**
     * @var string|null
     */
    private $state;

    /**
     * @var Zip|null
     */
    private $zip;

    /**
     * @var CountryCode|null
     */
    private $countryCode;

    /**
     * @var string|null
     */
    private $routingNumber;

    /**
     * NonPCIPaymentFormData constructor.
     *
     * @param Bin|null         $bin
     * @param LastFour|null    $lastFour
     * @param Email|null       $email
     * @param Zip|null         $zip
     * @param CountryCode|null $countryCode
     * @param FirstName|null   $firstName
     * @param LastName|null    $lastName
     * @param string|null      $street
     * @param string|null      $city
     * @param string|null      $state
     * @param string|null      $routingNumber
     */
    private function __construct(
        ?Bin $bin,
        ?LastFour $lastFour,
        ?Email $email,
        ?Zip $zip = null,
        ?CountryCode $countryCode = null,
        ?FirstName $firstName = null,
        ?LastName $lastName = null,
        ?string $street = null,
        ?string $city = null,
        ?string $state = null,
        ?string $routingNumber = null
    ) {
        $this->bin           = $bin;
        $this->lastFour      = $lastFour;
        $this->email         = $email;
        $this->firstName     = $firstName;
        $this->lastName      = $lastName;
        $this->street        = $street;
        $this->city          = $city;
        $this->state         = $state;
        $this->zip           = $zip;
        $this->countryCode   = $countryCode;
        $this->routingNumber = $routingNumber;
    }

    /**
     * @param Bin|null      $bin
     * @param LastFour|null $lastFour
     * @param FirstName     $firstName
     * @param LastName      $lastName
     * @param Email|null    $email
     * @param Zip           $zip
     * @param CountryCode   $countryCode
     * @param string|null   $street
     * @param string|null   $city
     * @param string|null   $state
     *
     * @param string|null   $routingNumber
     *
     * @return void
     */
    public static function create(
        ?Bin $bin,
        ?LastFour $lastFour,
        FirstName $firstName,
        LastName $lastName,
        ?Email $email,
        Zip $zip,
        CountryCode $countryCode,
        ?string $street = null,
        ?string $city = null,
        ?string $state = null,
        ?string $routingNumber = null
    ) {
        return new self(
            $bin,
            $lastFour,
            $email,
            $zip,
            $countryCode,
            $firstName,
            $lastName,
            $street,
            $city,
            $state,
            $routingNumber
        );
    }

    /**
     * @param Bin|null         $bin
     * @param LastFour|null    $lastFour
     * @param Email|null       $email
     * @param Zip|null         $zip
     * @param CountryCode|null $countryCode
     * @param FirstName|null   $firstName
     * @param null|LastName    $lastName
     *
     * @param string|null      $routingNumber
     *
     * @return NonPCIPaymentFormData
     */
    public static function createForProcessCustomer(
        ?Bin $bin,
        ?LastFour $lastFour,
        ?Email $email,
        ?Zip $zip,
        ?CountryCode $countryCode,
        ?FirstName $firstName,
        ?LastName $lastName,
        ?string $routingNumber
    ) {
        return new self(
            $bin,
            $lastFour,
            $email,
            $zip,
            $countryCode,
            $firstName,
            $lastName,
            null,
            null,
            null,
            $routingNumber
        );
    }

    /**
     * @return Bin|null
     */
    public function bin(): ?Bin
    {
        return $this->bin;
    }

    /**
     * @return LastFour|null
     */
    public function lastFour(): ?LastFour
    {
        return $this->lastFour;
    }

    /**
     * @return FirstName|null
     */
    public function firstName(): ?FirstName
    {
        return $this->firstName;
    }

    /**
     * @return LastName|null
     */
    public function lastName(): ?LastName
    {
        return $this->lastName;
    }

    /**
     * @return Email|null
     */
    public function email(): ?Email
    {
        return $this->email;
    }

    /**
     * @return string|null
     */
    public function street(): ?string
    {
        return $this->street;
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
     * @return Zip|null
     */
    public function zip(): ?Zip
    {
        return $this->zip;
    }

    /**
     * @return CountryCode|null
     */
    public function countryCode(): ?CountryCode
    {
        return $this->countryCode;
    }

    /**
     * @return string|null
     */
    public function routingNumber(): ?string
    {
        return $this->routingNumber;
    }
}
