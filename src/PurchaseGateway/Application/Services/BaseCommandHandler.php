<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services;

use ProBillerNG\Base\Domain\DomainEventCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Purchase;
use ProBillerNG\Base\Application\Services\CommandHandler;
use ProBillerNG\Base\Application\Services\ExposeDomainEvents;

abstract class BaseCommandHandler implements CommandHandler, ExposeDomainEvents, ExposeIntegrationEvents
{
    use IntegrationEventHandling;

    /**
     * @var Purchase
     */
    protected $purchase;

    /**
     * @var IntegrationEventCollection
     */
    protected $integrationEvents;

    /**
     * @return DomainEventCollection
     */
    public function events(): DomainEventCollection
    {
        $events = new DomainEventCollection();

        if ($this->purchase instanceof Purchase) {
            $events = $this->purchase->events();
        }

        return $events;
    }

    /**
     * @return void
     */
    public function clearEvents(): void
    {
        if ($this->purchase instanceof Purchase) {
            $this->purchase->clearEvents();
        }
    }
}
