<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\Event;

abstract class CCPurchaseEvent extends PurchaseEvent
{
    /**
     * @var string|null
     */
    protected $first6;

    /**
     * @var string|null
     */
    protected $last4;

    /**
     * @var string|null
     */
    protected $cardExpirationYear;

    /**
     * @var string|null
     */
    protected $cardExpirationMonth;

    /**
     * CCPurchaseEvent constructor.
     *
     * @param string             $aggregateId Aggregate id
     * @param \DateTimeImmutable $occurredOn  Created on
     * @param string             $billerName  Biller name
     * @param string             $purchaseId  Purchase UUID
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
     * @param string|null        $first6      First 6
     * @param string|null        $last4       Last 4
     * @param string             $username    Username
     * @param string             $password    Password
     * @param string|null        $atlasCode   Atlas Code
     * @param string|null        $atlasData   Atlas Data
     * @param string|null        $ipAddress   Ip address
     *
     * @param string|null        $cardExpirationYear
     * @param string|null        $cardExpirationMonth
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
        ?string $first6,
        ?string $last4,
        string $username,
        string $password,
        ?string $atlasCode,
        ?string $atlasData,
        ?string $ipAddress,
        ?string $cardExpirationYear,
        ?string $cardExpirationMonth
    ) {
        parent::__construct(
            $aggregateId,
            $occurredOn,
            $billerName,
            $purchaseId,
            $currency,
            $paymentType,
            $memberId,
            $email,
            $phoneNumber,
            $firstName,
            $lastName,
            $address,
            $city,
            $state,
            $zip,
            $country,
            $username,
            $password,
            $atlasCode,
            $atlasData,
            $ipAddress
        );

        $this->first6              = $first6;
        $this->last4               = $last4;
        $this->cardExpirationYear  = $cardExpirationYear;
        $this->cardExpirationMonth = $cardExpirationMonth;
    }

    /**
     * @return string|null
     */
    public function first6(): ?string
    {
        return $this->first6;
    }

    /**
     * @return string|null
     */
    public function last4(): ?string
    {
        return $this->last4;
    }

    /**
     * @return string|null
     */
    public function cardExpirationYear(): ?string
    {
        return $this->cardExpirationYear;
    }

    /**
     * @return string|null
     */
    public function cardExpirationMonth(): ?string
    {
        return $this->cardExpirationMonth;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return array_merge(get_object_vars($this), parent::toArray());
    }
}
