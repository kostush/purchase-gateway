<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\Event;

use ProBillerNG\PurchaseGateway\Application\Exceptions\AgregateIdNotSetOnEvent;
use ProBillerNG\PurchaseGateway\Application\Services\IntegrationEvent;
use ProBillerNG\PurchaseGateway\Domain\Event;

abstract class BaseEvent implements IntegrationEvent, Event
{
    const LATEST_VERSION = 2;

    /**
     * @var string
     */
    protected $aggregateId;

    /**
     * @var \DateTimeImmutable
     */
    protected $occurredOn;

    /**
     * @var int
     */
    protected $version;

    /**
     * BaseEvent constructor.
     *
     * @param string                  $aggregateId Aggregate Id
     * @param \DateTimeImmutable|null $occurredOn  Occurred On
     * @throws \Exception
     */
    public function __construct(?string $aggregateId, ?\DateTimeImmutable $occurredOn)
    {
        $this->aggregateId = $aggregateId;
        $this->occurredOn  = $occurredOn ?: new \DateTimeImmutable();
        $this->version     = static::LATEST_VERSION;
    }

    /**
     * {@inheritdoc}
     *
     * @return \DateTimeImmutable
     */
    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function aggregateId(): string
    {
        if (null === $this->aggregateId) {
            throw new AgregateIdNotSetOnEvent(get_class($this));
        }
        return $this->aggregateId;
    }

    /**
     * @return int
     */
    public function version(): int
    {
        return $this->version;
    }
}
