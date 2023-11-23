<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

class UserInfo
{
    /**
     * @var Username|null
     */
    private $username;

    /**
     * @var Password|null
     */
    private $password;

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
     * @var Zip
     */
    private $zipCode;

    /**
     * @var string|null
     */
    private $city;

    /**
     * @var string|null
     */
    private $state;

    /**
     * @var CountryCode|null
     */
    private $country;

    /**
     * @var PhoneNumber|null
     */
    private $phoneNumber;

    /**
     * @var string|null
     */
    private $address;

    /**
     * @var string
     */
    private $ipAddress;

    /**
     * @var string|null
     */
    private $countryDetectedByIp;

    /**
     * UserInfo constructor.
     *
     * @param Username|null    $username            user name
     * @param Password|null    $password            password
     * @param FirstName|null   $firstName           first name
     * @param LastName|null    $lastName            last name
     * @param Email|null       $email               email
     * @param Zip|null         $zipCode             zipCode
     * @param Ip               $ipAddress           ipAddress
     * @param string|null      $city                city
     * @param string|null      $state               state
     * @param CountryCode      $country             country
     * @param PhoneNumber|null $phoneNumber         phoneNumber
     * @param string|null      $address             address
     * @param CountryCode|null $countryDetectedByIp countryDetectedByIp
     */
    private function __construct(
        ?Username $username,
        ?Password $password,
        ?FirstName $firstName,
        ?LastName $lastName,
        ?Email $email,
        ?Zip $zipCode,
        Ip $ipAddress,
        ?string $city,
        ?string $state,
        CountryCode $country,
        ?PhoneNumber $phoneNumber,
        ?string $address,
        ?CountryCode $countryDetectedByIp
    ) {
        $this->username            = $username;
        $this->password            = $password;
        $this->firstName           = $firstName;
        $this->lastName            = $lastName;
        $this->email               = $email;
        $this->zipCode             = $zipCode;
        $this->country             = $country;
        $this->phoneNumber         = $phoneNumber;
        $this->city                = $city;
        $this->state               = $state;
        $this->address             = $address;
        $this->ipAddress           = $ipAddress;
        $this->countryDetectedByIp = $countryDetectedByIp;
    }

    /**
     * @param CountryCode      $country             Country
     * @param Ip               $ipAddress           The user ip
     * @param Email|null       $email               Email
     * @param Username|null    $username            User name
     * @param Password|null    $password            Password
     * @param FirstName|null   $firstName           First name
     * @param LastName|null    $lastName            Last name
     * @param Zip|null         $zipCode             zipCode
     * @param string|null      $city                city
     * @param string|null      $state               state
     * @param PhoneNumber|null $phoneNumber         phoneNumber
     * @param string|null      $address             address
     * @param CountryCode|null $countryDetectedByIp countryDetectedByIp
     *
     * @return self
     */
    public static function create(
        CountryCode $country,
        Ip $ipAddress,
        ?Email $email = null,
        ?Username $username = null,
        ?Password $password = null,
        ?FirstName $firstName = null,
        ?LastName $lastName = null,
        ?Zip $zipCode = null,
        ?string $city = null,
        ?string $state = null,
        ?PhoneNumber $phoneNumber = null,
        ?string $address = null,
        ?CountryCode $countryDetectedByIp = null
    ): self {
        return new self(
            $username,
            $password,
            $firstName,
            $lastName,
            $email,
            $zipCode,
            $ipAddress,
            $city,
            $state,
            $country,
            $phoneNumber,
            $address,
            $countryDetectedByIp
        );
    }

    /**
     * @return Username|null
     */
    public function username(): ?Username
    {
        return $this->username;
    }

    /**
     * @return Password|null
     */
    public function password(): ?Password
    {
        return $this->password;
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
     * @return Zip|null
     */
    public function zipCode(): ?Zip
    {
        return $this->zipCode;
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
     * @return CountryCode
     */
    public function countryCode(): CountryCode
    {
        return $this->country;
    }

    /**
     * @return PhoneNumber|null
     */
    public function phoneNumber(): ?PhoneNumber
    {
        return $this->phoneNumber;
    }

    /**
     * @return string|null
     */
    public function address(): ?string
    {
        return $this->address;
    }

    /**
     * @return Ip
     */
    public function ipAddress(): Ip
    {
        return $this->ipAddress;
    }

    /**
     * @return CountryCode
     */
    public function countryCodeDetectedByIp(): ?CountryCode
    {
        return $this->countryDetectedByIp;
    }

    /**
     * @param Username $username The username
     * @return void
     */
    public function setUsername(Username $username): void
    {
        $this->username = $username;
    }

    /**
     * @param Password $password The password
     * @return void
     */
    public function setPassword(Password $password): void
    {
        $this->password = $password;
    }

    /**
     * @param FirstName $firstName The first name
     * @return void
     */
    public function setFirstName(FirstName $firstName): void
    {
        $this->firstName = $firstName;
    }

    /**
     * @param LastName $lastName The last name
     * @return void
     */
    public function setLastName(LastName $lastName): void
    {
        $this->lastName = $lastName;
    }

    /**
     * @param Email $email The email
     * @return void
     */
    public function setEmail(Email $email): void
    {
        $this->email = $email;
    }

    /**
     * @param Zip $zipCode The zip code
     */
    public function setZipCode(Zip $zipCode): void
    {
        $this->zipCode = $zipCode;
    }

    /**
     * @param string|null $city The city
     */
    public function setCity(?string $city): void
    {
        $this->city = $city;
    }

    /**
     * @param string|null $state The user state
     */
    public function setState(?string $state): void
    {
        $this->state = $state;
    }

    /**
     * @param CountryCode countryCode The user country
     */
    public function setCountryCode(CountryCode $countryCode): void
    {


        $this->country = $countryCode;
    }

    /**
     * @param PhoneNumber|null $phoneNumber The phone number
     */
    public function setPhoneNumber(?PhoneNumber $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    /**
     * @param string|null $address The address
     */
    public function setAddress(?string $address): void
    {
        $this->address = $address;
    }

    /**
     * @param CountryCode countryCode The user country from init request
     */
    public function setCountryCodeDetectedByIp(CountryCode $countryCodeDetectedByIp): void
    {
        $this->countryDetectedByIp = $countryCodeDetectedByIp;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'address'             => $this->address(),
            'city'                => $this->city(),
            'country'             => (string) $this->countryCode(),
            'email'               => (string) $this->email(),
            'firstName'           => (string) $this->firstName(),
            'ipAddress'           => (string) $this->ipAddress(),
            'lastName'            => (string) $this->lastName(),
            'password'            => (string) $this->password(),
            'phoneNumber'         => (string) $this->phoneNumber(),
            'state'               => $this->state(),
            'username'            => (string) $this->username(),
            'zipCode'             => (string) $this->zipCode(),
            'countryDetectedByIp' => (string) $this->countryCodeDetectedByIp()
        ];
    }
}
