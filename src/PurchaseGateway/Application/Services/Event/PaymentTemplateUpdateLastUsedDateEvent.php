<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\Event;

class PaymentTemplateUpdateLastUsedDateEvent extends PaymentTemplateBaseEvent
{
    /**
     * Integration event name
     * @var string
     */
    public const INTEGRATION_NAME = 'ProbillerNG\\Events\\PaymentTemplateUpdateLastUsedDateEvent';

    /**
     * @var string
     */
    private $paymentTemplateId;

    /**
     * @var \DateTimeImmutable
     */
    private $lastUsedDate;

    /**
     * @var array|null
     */
    private $billerFields;

    /**
     * PaymentTemplateUpdateLastUsedDateEvent constructor.
     * @param string             $paymentTemplateId Payment Template Id
     * @param \DateTimeImmutable $occurredOn        Occurred On
     * @param array|null         $billerFields      Biller Fields
     * @throws \Exception
     */
    public function __construct(
        string $paymentTemplateId,
        \DateTimeImmutable $occurredOn,
        ?array $billerFields
    ) {
        parent::__construct($paymentTemplateId, $occurredOn);

        $this->paymentTemplateId = $paymentTemplateId;
        $this->lastUsedDate      = $occurredOn;
        $this->billerFields      = $billerFields;
    }

    /**
     * @return string
     */
    public function paymentTemplateId(): string
    {
        return $this->paymentTemplateId;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function lastUsedDate(): \DateTimeImmutable
    {
        return $this->lastUsedDate;
    }

    /**
     * @return array|null
     */
    public function billerFields(): ?array
    {
        return $this->billerFields;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'type'         => self::INTEGRATION_NAME,
            'templateId'   => $this->paymentTemplateId(),
            'lastUsedDate' => $this->lastUsedDate(),
            'billerFields' => $this->billerFields(),
            'message_type' => 'event'
        ];
    }
}
