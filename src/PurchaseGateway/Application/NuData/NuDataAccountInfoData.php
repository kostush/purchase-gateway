<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\NuData;

class NuDataAccountInfoData
{
    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $firstName;

    /**
     * @var string
     */
    private $lastName;

    /**
     * @var string|null
     */
    private $phone;

    /**
     * @var string|null
     */
    private $address;

    /**
     * @var string|null
     */
    private $city;

    /**
     * @var string|null
     */
    private $state;

    /**
     * @var string|null
     */
    private $country;

    /**
     * @var string|null
     */
    private $zipCode;

    /**
     * NuDataAccountInfoData constructor.
     * @param string      $username  Username
     * @param string      $password  Password
     * @param string      $email     Email
     * @param string      $firstName First Name
     * @param string      $lastName  Last Name
     * @param string|null $phone     Phone
     * @param string|null $address   Address
     * @param string|null $city      City
     * @param string|null $state     State
     * @param string|null $country   Country
     * @param string|null $zipCode   Zip Code
     */
    public function __construct(
        string $username,
        string $password,
        string $email,
        string $firstName,
        string $lastName,
        ?string $phone,
        ?string $address,
        ?string $city,
        ?string $state,
        ?string $country,
        ?string $zipCode
    ) {
        $this->username  = $username;
        $this->password  = $password;
        $this->email     = $email;
        $this->firstName = $firstName;
        $this->lastName  = $lastName;
        $this->phone     = $phone;
        $this->address   = $address;
        $this->city      = $city;
        $this->state     = $state;
        $this->country   = $country;
        $this->zipCode   = $zipCode;
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
    public function phone(): ?string
    {
        return $this->phone;
    }

    /**
     * @return string|null
     */
    public function address(): ?string
    {
        return $this->address;
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
    public function zipCode(): ?string
    {
        return $this->zipCode;
    }
}
