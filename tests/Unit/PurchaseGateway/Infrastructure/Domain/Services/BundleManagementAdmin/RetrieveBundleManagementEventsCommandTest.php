<?php

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\BundleManagementAdmin;

use Odesk\Phystrix\Exception\RuntimeException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BundleManagementAdmin\RetrieveBundleManagementEventsAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BundleManagementAdmin\RetrieveBundleManagementEventsCommand;
use Tests\UnitTestCase;

class RetrieveBundleManagementEventsCommandTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_empty_array_when_the_circuit_is_opened(): void
    {
        $adapterMock = $this->createMock(RetrieveBundleManagementEventsAdapter::class);
        $adapterMock->method('retrieveEvents')->willThrowException(
            new RuntimeException(
                '',
                RetrieveBundleManagementEventsCommand::class
            )
        );

        $command = $this->getCircuitBreakerCommandFactory()->getCommand(
            RetrieveBundleManagementEventsCommand::class,
            $adapterMock,
            0,
            10
        );

        $result = $command->execute();

        $this->assertEquals([], $result);
    }
}
