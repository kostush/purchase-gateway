<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services;

interface ExposeIntegrationEvents
{
    /**
     * @return IntegrationEventCollection
     */
    public function integrationEvents(): IntegrationEventCollection;

    /**
     * @return void
     */
    public function clearIntegrationEvents(): void;

    /**
     * @param IntegrationEvent $event Integration event
     * @return void
     */
    public function addIntegrationEvent(IntegrationEvent $event): void;
}
