<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\Event;

class PaymentTemplateCreatedEvent extends PaymentTemplateBaseEvent
{
    const LATEST_VERSION = 5;

    /**
     * Integration event name
     * @var string
     */
    public const INTEGRATION_NAME = 'ProbillerNG\\Events\\PaymentTemplateCreateEvent';

    /**
     * @var string
     */
    private $paymentType;

    /**
     * @var string|null
     */
    private $first6;

    /**
     * @var string|null
     */
    private $last4;

    /**
     * @var int|null
     */
    private $expirationYear;

    /**
     * @var int|null
     */
    private $expirationMonth;

    /**
     * @var string
     */
    private $billerName;

    /**
     * @var string
     */
    private $memberId;

    /**
     * @var \DateTimeImmutable
     */
    private $createdAt;

    /**
     * @var array
     */
    private $billerFields;

    /**
     * PaymentTemplateCreatedEvent constructor.
     * @param string|null        $aggregateId     AggregateId
     * @param string             $paymentType     PaymentType
     * @param string|null        $first6          First6
     * @param string|null        $last4           Last4
     * @param int|null           $expirationYear  ExpirationYear
     * @param int|null           $expirationMonth ExpirationMonth
     * @param \DateTimeImmutable $occurredOn      Occurred on
     * @param string             $billerName      The biller name this template will apply to
     * @param string             $memberId        Member Id
     * @param array              $billerFields    Biller Fields
     * @throws \Exception
     */
    public function __construct(
        ?string $aggregateId,
        string $paymentType,
        ?string $first6,
        ?string $last4,
        ?int $expirationYear,
        ?int $expirationMonth,
        \DateTimeImmutable $occurredOn,
        string $billerName,
        string $memberId,
        array $billerFields
    ) {
        parent::__construct($aggregateId, $occurredOn);

        $this->paymentType     = $paymentType;
        $this->first6          = $first6;
        $this->last4           = $last4;
        $this->expirationYear  = $expirationYear;
        $this->expirationMonth = $expirationMonth;
        $this->billerName      = $billerName;
        $this->memberId        = $memberId;
        $this->createdAt       = $occurredOn;
        $this->billerFields    = $billerFields;
    }

    /**
     * @return string
     */
    public function paymentType(): string
    {
        return $this->paymentType;
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
     * @return int|null
     */
    public function expirationYear(): ?int
    {
        return $this->expirationYear;
    }

    /**
     * @return int|null
     */
    public function expirationMonth(): ?int
    {
        return $this->expirationMonth;
    }

    /**
     * @return string
     */
    public function billerName(): string
    {
        return $this->billerName;
    }

    /**
     * @return string
     */
    public function memberId(): string
    {
        return $this->memberId;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return array
     */
    public function billerFields(): array
    {
        return $this->billerFields;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'type'            => self::INTEGRATION_NAME,
            'memberId'        => $this->memberId(),
            'paymentType'     => $this->paymentType(),
            'firstSix'        => (string) $this->first6(),
            'lastFour'        => (string) $this->last4(),
            'expirationYear'  => (string) $this->expirationYear(),
            'expirationMonth' => (string) $this->expirationMonth(),
            'billerName'      => $this->billerName(),
            'billerFields'    => $this->billerFields(),
            'createdAt'       => $this->createdAt(),
            'message_type'    => 'event'
        ];
    }
}
