<?php

namespace Tests\Unit\PurchaseGateway\Application\Services\ThirdPartyPostback;

use Exception;
use ProBillerNG\PurchaseGateway\Application\Exceptions\NoBodyOrHeaderReceivedException;
use ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyPostback\ThirdPartyPostbackCommand;
use ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyPostback\ThirdPartyPostbackCommandHandlerFactory;
use Tests\UnitTestCase;

class ThirdPartyPostbackCommandTest extends UnitTestCase
{
    /**
     * @test
     * @return ThirdPartyPostbackCommand
     * @throws Exception
     */
    public function it_should_return_a_valid_postback_command(): ThirdPartyPostbackCommand
    {
        $command = new ThirdPartyPostbackCommand(
            'd88a4689-9b32-426d-877e-fa33fbcfb475',
            ['ngTransactionId' => '11ec1d28-b068-4d07-ae25-c917e318cd95'],
            ThirdPartyPostbackCommandHandlerFactory::CHARGE
        );

        $this->assertInstanceOf(ThirdPartyPostbackCommand::class, $command);

        return $command;
    }

    /**
     * @test
     * @depends it_should_return_a_valid_postback_command
     * @param ThirdPartyPostbackCommand $command Postback command
     * @return void
     */
    public function it_should_contain_correct_session_id(ThirdPartyPostbackCommand $command): void
    {
        $this->assertSame('d88a4689-9b32-426d-877e-fa33fbcfb475', (string) $command->sessionId());
    }

    /**
     * @test
     * @depends it_should_return_a_valid_postback_command
     * @param ThirdPartyPostbackCommand $command Postback command
     * @return void
     */
    public function it_should_contain_correct_payload(ThirdPartyPostbackCommand $command): void
    {
        $this->assertSame(
            ['ngTransactionId' => '11ec1d28-b068-4d07-ae25-c917e318cd95'],
            $command->payload()
        );
    }

    /**
     * @test
     * @depends it_should_return_a_valid_postback_command
     * @param ThirdPartyPostbackCommand $command Postback command
     * @return void
     */
    public function it_should_contain_correct_transaction_id(ThirdPartyPostbackCommand $command): void
    {
        $this->assertSame('11ec1d28-b068-4d07-ae25-c917e318cd95', $command->transactionId());
    }

    /**
     * @test
     * @return void
     * @throws NoBodyOrHeaderReceivedException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_contain_correct_transaction_id_from_qysso(): void
    {
        $command = new ThirdPartyPostbackCommand(
            'd88a4689-9b32-426d-877e-fa33fbcfb475',
            ['trans_order' => '11111111-b068-4d07-ae25-c917e318cd95'],
            ThirdPartyPostbackCommandHandlerFactory::CHARGE
        );

        $this->assertSame('11111111-b068-4d07-ae25-c917e318cd95', $command->transactionId());
    }

    /**
     * @test
     * @return void
     * @throws NoBodyOrHeaderReceivedException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_contain_empty_transaction_id(): void
    {
        $command = new ThirdPartyPostbackCommand(
            'd88a4689-9b32-426d-877e-fa33fbcfb475',
            ['test' => ''],
            ThirdPartyPostbackCommandHandlerFactory::CHARGE
        );

        $this->assertEmpty($command->transactionId());
    }

    /**
     * @test
     * @depends it_should_return_a_valid_postback_command
     * @param ThirdPartyPostbackCommand $command Postback command
     * @return void
     */
    public function it_should_contain_correct_type(ThirdPartyPostbackCommand $command): void
    {
        $this->assertSame(ThirdPartyPostbackCommandHandlerFactory::CHARGE, $command->type());
    }

    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function it_should_throw_no_header_body_received_exception(): void
    {
        $this->expectException(NoBodyOrHeaderReceivedException::class);

        new ThirdPartyPostbackCommand(
            'd88a4689-9b32-426d-877e-fa33fbcfb475',
            [],
            ThirdPartyPostbackCommandHandlerFactory::CHARGE
        );
    }

    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function it_should_throw_invalid_argument_when_session_id_is_invalid_uuid_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ThirdPartyPostbackCommand(
            'invalid-uuid',
            [
                'payload' => ['transactionId' => '11ec1d28-b068-4d07-ae25-c917e318cd95']
            ],
            ThirdPartyPostbackCommandHandlerFactory::CHARGE
        );
    }
}
