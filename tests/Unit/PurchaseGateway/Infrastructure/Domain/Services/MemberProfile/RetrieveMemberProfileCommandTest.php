<?php

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\MemberProfile;

use Odesk\Phystrix\Exception\RuntimeException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\MemberProfileGateway\RetrieveMemberProfileCommand;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\MemberProfileGateway\RetrieveMemberProfileServiceAdapter;
use Tests\UnitTestCase;

class RetrieveMemberProfileCommandTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_an_exception_when_the_circuit_is_opened(): void
    {
        $this->expectException(RuntimeException::class);

        $adapterMock = $this->createMock(RetrieveMemberProfileServiceAdapter::class);
        $adapterMock->method('retrieveMemberProfile')->willThrowException(new RuntimeException('', RetrieveMemberProfileCommand::class));

        $command = $this->getCircuitBreakerCommandFactory()->getCommand(
            RetrieveMemberProfileCommand::class,
            $adapterMock,
            $this->faker->uuid,
            $this->faker->uuid,
            $this->faker->word,
            $this->faker->uuid,
            $this->faker->uuid,
            null
        );

        $command->execute();
    }
}
