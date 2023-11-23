<?php

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\EmailService;

use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\CreateSendEmailCommand;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\EmailServiceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\Overrides;
use Tests\UnitTestCase;

class CreateSendEmailCommandTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_an_exception_when_the_circuit_is_opened(): void
    {
        $this->expectException(\Exception::class);

        $adapterMock = $this->createMock(EmailServiceAdapter::class);
        $adapterMock->method('send')->willThrowException(new \Exception());

        $command = $this->getCircuitBreakerCommandFactory()->getCommand(
            CreateSendEmailCommand::class,
            $adapterMock,
            $this->faker->uuid,
            Email::create($this->faker->email),
            [],
            Overrides::create(),
            SessionId::create(),
            $this->faker->uuid
        );

        $command->execute();
    }
}
