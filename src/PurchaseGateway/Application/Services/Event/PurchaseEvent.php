<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\Event;

abstract class PurchaseEvent extends BaseEvent
{
    /**
     * @var string
     */
    protected $billerName;

    /**
     * @var string
     */
    protected $purchaseId;

    /**
     * @var string
     */
    protected $currency;

    /**
     * @var string
     */
    protected $paymentType;

    /**
     * @var string
     */
    protected $memberId;

    /**
     * @var string|null
     */
    protected $email;

    /**
     * @var string|null
     */
    protected $phoneNumber;

    /**
     * @var string|null
     */
    protected $firstName;

    /**
     * @var string|null
     */
    protected $lastName;

    /**
     * @var string|null
     */
    protected $address;

    /**
     * @var string|null
     */
    protected $city;

    /**
     * @var string|null
     */
    protected $state;

    /**
     * @var string|null
     */
    protected $zip;

    /**
     * @var string|null
     */
    protected $country;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var string|null
     */
    protected $atlasCode;

    /**
     * @var string|null
     */
    protected $atlasData;

    /**
     * @var string|null
     */
    protected $ipAddress;

    /**
     * @var PurchasedItemDetails[]
     */
    protected $items = [];

    /**
     * PurchaseEvent constructor.
     *
     * @param string             $aggregateId Aggregate id
     * @param \DateTimeImmutable $occurredOn  Created on
     * @param string             $billerName  Biller name
     * @param string             $purchaseId  Purchase UUID.
     * @param string             $currency    Currency
     * @param string             $paymentType Payment type
     * @param string             $memberId    Member Id
     * @param null|string        $email       Email
     * @param null|string        $phoneNumber Phone number
     * @param null|string        $firstName   First name
     * @param null|string        $lastName    Last name
     * @param null|string        $address     Address
     * @param null|string        $city        City
     * @param null|string        $state       State
     * @param null|string        $zip         Zip
     * @param null|string        $country     Country
     * @param string             $username    Username
     * @param string             $password    Password
     * @param string|null        $atlasCode   Atlas tracking code
     * @param string|null        $atlasData   Atlas data
     * @param string|null        $ipAddress   Ip address
     *
     * @throws \Exception
     */
    public function __construct(
        string $aggregateId,
        \DateTimeImmutable $occurredOn,
        string $billerName,
        string $purchaseId,
        string $currency,
        string $paymentType,
        string $memberId,
        ?string $email,
        ?string $phoneNumber,
        ?string $firstName,
        ?string $lastName,
        ?string $address,
        ?string $city,
        ?string $state,
        ?string $zip,
        ?string $country,
        string $username,
        string $password,
        ?string $atlasCode,
        ?string $atlasData,
        ?string $ipAddress
    ) {
        parent::__construct($aggregateId, $occurredOn);

        $this->billerName  = $billerName;
        $this->purchaseId  = $purchaseId;
        $this->currency    = $currency;
        $this->paymentType = $paymentType;
        $this->memberId    = $memberId;
        $this->email       = $email;
        $this->phoneNumber = $phoneNumber;
        $this->firstName   = $firstName;
        $this->lastName    = $lastName;
        $this->address     = $address;
        $this->city        = $city;
        $this->state       = $state;
        $this->zip         = $zip;
        $this->country     = $country;
        $this->username    = $username;
        $this->password    = $password;
        $this->atlasCode   = $atlasCode;
        $this->atlasData   = $atlasData;
        $this->ipAddress   = $ipAddress;
    }

    /**
     * @param PurchasedItemDetails $item Item
     *
     * @return void
     */
    public function addItem(
        PurchasedItemDetails $item
    ): void {
        $this->items[] = $item;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $arrayData = get_object_vars($this);
        /** @var PurchasedItemDetails $item */
        foreach ($arrayData['items'] as $key => $item) {
            $arrayData['items'][$key] = $item->toArray();
        }

        return $arrayData;
    }
}
