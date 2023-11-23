<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services;

interface IntegrationEventSubscriber
{
    /**
     * @param IntegrationEvent $event Domain event
     * @return mixed
     */
    public function handle(IntegrationEvent $event);

    /**
     * @param mixed $event Event
     * @return bool
     */
    public function isSubscribedTo($event): bool;
}
