<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

use ProbillerNG\TransactionServiceClient\Model\RetrieveTransaction;

class MemberInformation
{
    /**
     * @var string|null
     */
    private $email;

    /**
     * @var string|null
     */
    private $phoneNumber;

    /**
     * @var string|null
     */
    private $firstName;

    /**
     * @var string|null
     */
    private $lastName;

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
    private $zip;

    /**
     * @var string|null
     */
    private $country;

    /**
     * @var string|null
     */
    private $name;

    /**
     * MemberInformation constructor.
     * @param RetrieveTransaction $response Api response
     */
    public function __construct(
        RetrieveTransaction $response
    ) {
        if (!$response->getMember()) {
            return;
        }
        $this->email       = $response->getMember()->getEmail();
        $this->phoneNumber = $response->getMember()->getPhoneNumber();
        $this->firstName   = $response->getMember()->getFirstName();
        $this->lastName    = $response->getMember()->getLastName();
        $this->address     = $response->getMember()->getAddress();
        $this->city        = $response->getMember()->getCity();
        $this->state       = $response->getMember()->getState();
        $this->zip         = $response->getMember()->getZip();
        $this->country     = $response->getMember()->getCountry();
        $this->name        = $response->getMember()->getName();
    }

    /**
     * @return null|string
     */
    public function email(): ?string
    {
        return $this->email;
    }

    /**
     * @return null|string
     */
    public function phoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    /**
     * @return null|string
     */
    public function firstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @return null|string
     */
    public function lastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @return null|string
     */
    public function address(): ?string
    {
        return $this->address;
    }

    /**
     * @return null|string
     */
    public function city(): ?string
    {
        return $this->city;
    }

    /**
     * @return null|string
     */
    public function state(): ?string
    {
        return $this->state;
    }

    /**
     * @return null|string
     */
    public function zip(): ?string
    {
        return $this->zip;
    }

    /**
     * @return null|string
     */
    public function country(): ?string
    {
        return $this->country;
    }

    /**
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->name;
    }
}
