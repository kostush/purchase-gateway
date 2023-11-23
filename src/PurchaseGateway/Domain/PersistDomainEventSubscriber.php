<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain;

use ProBillerNG\Base\Domain\DomainEvent;
use ProBillerNG\Base\Domain\DomainEventSubscriber;

class PersistDomainEventSubscriber implements DomainEventSubscriber
{
    /** @var EventStore */
    protected $eventStore;

    /**
     * PersistDomainEventSubscriber constructor.
     * @param EventStore $eventStore Event
     */
    public function __construct(EventStore $eventStore)
    {
        $this->eventStore = $eventStore;
    }

    /**
     * @param DomainEvent $event Event
     * @return mixed|void
     */
    public function handle(DomainEvent $event)
    {
        $this->eventStore->append($event);
    }

    /**
     * @param mixed $event Event
     * @return bool
     */
    public function isSubscribedTo($event): bool
    {
        // I want all events!
        return true;
    }
}
