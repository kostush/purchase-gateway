<?php

namespace Tests\Unit\PurchaseGateway\Application\Services\ThirdPartyPostback;

use Exception;
use ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyReturn\ReturnCommand;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use Tests\UnitTestCase;

class ReturnCommandTest extends UnitTestCase
{
    /**
     * @test
     * @return ReturnCommand
     * @throws Exception
     */
    public function it_should_return_a_valid_postback_command_with_epoch_payload(): ReturnCommand
    {
        $command = new ReturnCommand(
            ['ngTransactionId' => '83245182-40ce-4642-940a-e4873bceeddf'],
            '71c41295-5565-43dc-863a-8f30287d5a77'
        );

        $this->assertInstanceOf(ReturnCommand::class, $command);

        return $command;
    }

    /**
     * @test
     * @depends it_should_return_a_valid_postback_command_with_epoch_payload
     * @param ReturnCommand $returnCommand Third party return command.
     * @return void
     */
    public function it_should_return_session_id_instance(ReturnCommand $returnCommand): void
    {
        $this->assertInstanceOf(SessionId::class, $returnCommand->sessionId());
    }

    /**
     * @test
     * @depends it_should_return_a_valid_postback_command_with_epoch_payload
     * @param ReturnCommand $returnCommand Third party return command.
     * @return void
     */
    public function it_should_contain_correct_session_id(ReturnCommand $returnCommand): void
    {
        $this->assertSame('71c41295-5565-43dc-863a-8f30287d5a77', (string) $returnCommand->sessionId());
    }

    /**
     * @test
     * @depends it_should_return_a_valid_postback_command_with_epoch_payload
     * @param ReturnCommand $returnCommand Third party return command.
     * @return void
     */
    public function it_should_contain_correct_transaction_id_from_epoch(ReturnCommand $returnCommand): void
    {
        $this->assertSame('83245182-40ce-4642-940a-e4873bceeddf', $returnCommand->transactionId());
    }

    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function it_should_return_empty_transaction_id(): void
    {
        $command = new ReturnCommand(
            [],
            '71c41295-5565-43dc-863a-8f30287d5a77'
        );

        $this->assertEmpty($command->transactionId());
    }

    /**
     * @test
     * @return ReturnCommand
     * @throws Exception
     */
    public function it_should_return_a_valid_postback_command_with_qysso_payload(): ReturnCommand
    {
        $command = new ReturnCommand(
            ['Order' => '4c9abcd7-3b65-45c0-b043-3ed90407d752'],
            '0cbeebf3-13a5-4f12-b8ca-890bcb1e4024'
        );

        $this->assertInstanceOf(ReturnCommand::class, $command);

        return $command;
    }

    /**
     * @test
     * @depends it_should_return_a_valid_postback_command_with_qysso_payload
     * @param ReturnCommand $returnCommand Third party return command.
     * @return void
     */
    public function it_should_contain_correct_transaction_id_from_qysso(ReturnCommand $returnCommand): void
    {
        $this->assertSame('4c9abcd7-3b65-45c0-b043-3ed90407d752', $returnCommand->transactionId());
    }
}
