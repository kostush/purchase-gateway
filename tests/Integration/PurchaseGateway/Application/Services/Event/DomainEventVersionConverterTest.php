<?php

declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Application\Services\IntegrationEvent;

use ProBillerNG\PurchaseGateway\Application\Services\Event\DomainEventVersionConverter;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use Tests\IntegrationTestCase;

class DomainEventVersionConverterTest extends IntegrationTestCase
{
    /**
     * @test
     * @return void
     */
    public function it_should_return_the_exact_version_for_purchase_processed(): void
    {
        $domainEventConverter = new DomainEventVersionConverter();

        $this->assertSame(
            PurchaseProcessed::LATEST_VERSION,
            $domainEventConverter->getVersionByType(PurchaseProcessed::class)
        );
    }
}
