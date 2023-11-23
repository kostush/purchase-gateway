<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\BI\Processed;

class Member extends Base
{
    /**
     * @var string
     */
    protected $email;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $firstName;

    /**
     * @var string
     */
    protected $lastName;

    /**
     * @var string
     */
    protected $countryCode;

    /**
     * @var string
     */
    protected $zipCode;

    /**
     * @var string
     */
    protected $address;

    /**
     * @var string
     */
    protected $city;

    /**
     * @var string
     */
    protected $phone;

    /**
     * @param string      $email       Email Address.
     * @param string      $username    Username.
     * @param string|null $firstName   First Name
     * @param string|null $lastName    Last Name.
     * @param string|null $countryCode Country Code.
     * @param string|null $zipCode     Zip Code.
     * @param string|null $address     Address.
     * @param string|null $city        City
     * @param string|null $phone       Phone Number.
     */
    private function __construct(
        string $email,
        string $username,
        ?string $firstName,
        ?string $lastName,
        ?string $countryCode,
        ?string $zipCode,
        ?string $address,
        ?string $city,
        ?string $phone
    ) {
        $this->email       = $email;
        $this->username    = $username;
        $this->firstName   = $firstName;
        $this->lastName    = $lastName;
        $this->countryCode = $countryCode;
        $this->zipCode     = $zipCode;
        $this->address     = $address;
        $this->city        = $city;
        $this->phone       = $phone;
    }

    /**
     * @param string      $email       Email Address.
     * @param string      $username    Username.
     * @param string|null $firstName   First Name
     * @param string|null $lastName    Last Name
     * @param string|null $countryCode Country Code.
     * @param string|null $zipCode     Zip Code.
     * @param string|null $address     Address.
     * @param string|null $city        City
     * @param string|null $phone       Phone Number.
     *
     * @return Member
     */
    public static function create(
        string $email,
        string $username,
        ?string $firstName,
        ?string $lastName,
        ?string $countryCode,
        ?string $zipCode,
        ?string $address,
        ?string $city,
        ?string $phone
    ): self {
        return new static($email, $username, $firstName, $lastName, $countryCode, $zipCode, $address, $city, $phone);
    }
}
