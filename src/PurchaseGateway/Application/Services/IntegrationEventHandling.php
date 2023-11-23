<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services;

trait IntegrationEventHandling
{
    /**
     * @var IntegrationEventCollection
     */
    protected $integrationEvents;

    /**
     * @return IntegrationEventCollection
     */
    public function integrationEvents(): IntegrationEventCollection
    {
        if (!$this->integrationEvents instanceof IntegrationEventCollection) {
            $this->integrationEvents = new IntegrationEventCollection();
        }

        return $this->integrationEvents;
    }

    /**
     * @return void
     */
    public function clearIntegrationEvents(): void
    {
        $this->integrationEvents = new IntegrationEventCollection();
    }

    /**
     * @param IntegrationEvent $event Integration event
     * @return void
     */
    public function addIntegrationEvent(IntegrationEvent $event): void
    {
        if (!$this->integrationEvents instanceof IntegrationEventCollection) {
            $this->integrationEvents = new IntegrationEventCollection();
        }

        $this->integrationEvents->add($event);
    }

    /**
     * @return void
     */
    public function persistIntegrationEvents(): void
    {
        if (!empty($this->integrationEvents)) {
            foreach ($this->integrationEvents as $integrationEvent) {
                IntegrationEventPublisher::instance()->publish($integrationEvent);
            }

            $this->clearIntegrationEvents();
        }
    }
}
