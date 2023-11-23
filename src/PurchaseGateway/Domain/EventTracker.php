<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain;

class EventTracker
{
    const PURCHASE_DOMAIN_EVENT_TYPE    = 'PurchaseDomainEvent';
    const EVENT_TRACKER_TYPE_SEND_EMAIL = 'SendEmailEvent';
    const PAYMENT_TEMPLATE_CREATED_TYPE = 'PaymentTemplateCreatedDomainEvent';

    private static $eventTrackerTypes = [
        self::PURCHASE_DOMAIN_EVENT_TYPE,
        self::EVENT_TRACKER_TYPE_SEND_EMAIL,
        self::PAYMENT_TEMPLATE_CREATED_TYPE
    ];

    /**
     * @var EventTrackerId
     */
    private $eventTrackerId;

    /**
     * @var string
     */
    private $eventTrackerType;

    /**
     * @var \DateTimeImmutable|null
     */
    private $lastProcessedEventDate;

    /**
     * @var \DateTimeImmutable
     */
    private $createdOn;

    /**
     * @var \DateTimeImmutable
     */
    private $updatedOn;

    /**
     * EventTracker constructor.
     * @param EventTrackerId     $eventTrackerId         Id
     * @param string             $eventTrackerType       Type
     * @param \DateTimeImmutable $lastProcessedEventDate Last event date
     * @param \DateTimeImmutable $createdOn              Created on
     * @param \DateTimeImmutable $updatedOn              Updated on
     */
    public function __construct(
        EventTrackerId $eventTrackerId,
        string $eventTrackerType,
        \DateTimeImmutable $lastProcessedEventDate,
        ?\DateTimeImmutable $createdOn,
        ?\DateTimeImmutable $updatedOn
    ) {
        $this->eventTrackerId         = $eventTrackerId;
        $this->eventTrackerType       = $eventTrackerType;
        $this->lastProcessedEventDate = $lastProcessedEventDate;
        $this->createdOn              = $createdOn;
        $this->updatedOn              = $updatedOn;
    }

    /**
     * @param string $type Type
     * @return bool
     */
    public static function isEventTrackerTypes(string $type): bool
    {
        return in_array($type, self::$eventTrackerTypes);
    }

    /**
     * @return array
     */
    public static function getEventTrackerTypes(): array
    {
        return self::$eventTrackerTypes;
    }

    /**
     * @return EventTrackerId
     */
    public function eventTrackerId(): EventTrackerId
    {
        return $this->eventTrackerId;
    }

    /**
     * @return string
     */
    public function eventTrackerType(): string
    {
        return $this->eventTrackerType;
    }

    /**
     * @return \DateTimeImmutable|null
     */
    public function lastProcessedEventDate(): ?\DateTimeImmutable
    {
        return $this->lastProcessedEventDate;
    }

    /**
     * @param \DateTimeImmutable $lastProcessedEventDate Last processed event date
     * @return self
     */
    public function setLastProcessedEventDate(\DateTimeImmutable $lastProcessedEventDate): self
    {
        $this->lastProcessedEventDate = $lastProcessedEventDate;
        return $this;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function createdOn(): \DateTimeImmutable
    {
        return $this->createdOn;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function updatedOn(): \DateTimeImmutable
    {
        return $this->updatedOn;
    }

    /**
     * @param \DateTimeImmutable $updatedOn Updated on
     * @return self
     */
    public function setUpdatedOn(\DateTimeImmutable $updatedOn): self
    {
        $this->updatedOn = $updatedOn;
        return $this;
    }
}
