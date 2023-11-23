<?php

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\SiteAdmin;

use Odesk\Phystrix\Exception\RuntimeException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\SiteAdmin\RetrieveSiteAdminEventsAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\SiteAdmin\RetrieveSiteAdminEventsCommand;
use Tests\UnitTestCase;

class RetrieveSiteAdminEventsCommandTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_empty_array_when_the_circuit_is_opened(): void
    {
        $adapterMock = $this->createMock(RetrieveSiteAdminEventsAdapter::class);
        $adapterMock->method('retrieveEvents')->willThrowException(
            new RuntimeException(
                '',
                RetrieveSiteAdminEventsCommand::class
            )
        );

        $command = $this->getCircuitBreakerCommandFactory()->getCommand(
            RetrieveSiteAdminEventsCommand::class,
            $adapterMock,
            0,
            10
        );

        $result = $command->execute();

        $this->assertEquals([], $result);
    }
}
