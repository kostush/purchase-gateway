<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services;

use ProBillerNG\PurchaseGateway\Domain\EventStore;

class PersistIntegrationEventSubscriber implements IntegrationEventSubscriber
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
     * @param IntegrationEvent $event Event
     * @return mixed|void
     */
    public function handle(IntegrationEvent $event)
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
