<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Infrastructure\Domain\Services\Cascade;

use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;
use ProBillerNG\PurchaseGateway\Infrastructure\Services\Cascade\CascadeAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Services\Cascade\RetrieveCascadeCommand;
use Tests\UnitTestCase;

class RetrieveCascadeCommandTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_default_cascade_when_an_exception_is_encountered(): void
    {
        $adapterMock = $this->createMock(CascadeAdapter::class);
        $adapterMock->method('get')->willThrowException(new \Exception());

        $command = $this->getCircuitBreakerCommandFactory()->getCommand(
            RetrieveCascadeCommand::class,
            $adapterMock,
            $this->faker->uuid,
            $this->faker->uuid,
            $this->faker->uuid,
            $this->faker->countryCode,
            'cc',
            'visa',
            'ALL'
        );

        $cascade = $command->execute();

        $this->assertInstanceOf(Cascade::class, $cascade);
    }
}
