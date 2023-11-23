<?php

declare(strict_types=1);

namespace Tests;

use ProBillerNG\Base\Domain\DomainEventPublisher;
use ProBillerNG\PurchaseGateway\Application\Services\IntegrationEventPublisher;

trait ClearSingletons
{
    /**
     * Clear all singletons for the purpose of avoiding contamination
     * @return void
     */
    public function clearSingleton()
    {
        DomainEventPublisher::tearDown();
        IntegrationEventPublisher::tearDown();
    }
}
