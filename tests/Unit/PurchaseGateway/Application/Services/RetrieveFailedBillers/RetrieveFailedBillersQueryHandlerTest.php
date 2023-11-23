<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\RetrieveFailedBillers;

use ProBillerNG\PurchaseGateway\Application\DTO\RetrieveFailedBillers\FailedBillersQueryHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidQueryException;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Application\Services\RetrieveBillerTransaction\RetrieveItemQuery;
use ProBillerNG\PurchaseGateway\Application\Services\RetrieveFailedBillers\RetrieveFailedBillersQuery;
use ProBillerNG\PurchaseGateway\Application\Services\RetrieveFailedBillers\RetrieveFailedBillersQueryHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\FailedBillers;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\DatabasePurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Application\DTO\RetrieveFailedBillers\FailedBillersHttpQueryDTOAssembler;
use Tests\UnitTestCase;

class RetrieveFailedBillersQueryHandlerTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public function it_should_call_session_load_with_received_session_id(): void
    {
        $sessionId = $this->faker->uuid;

        $purchaseProcessHandlerMock = $this->getMockBuilder(DatabasePurchaseProcessHandler::class)
            ->onlyMethods(['load'])
            ->disableOriginalConstructor()
            ->getMock();

        $purchaseProcessHandlerMock->expects($this->once())->method('load')->with($sessionId);

        $handler = $this->getMockBuilder(RetrieveFailedBillersQueryHandler::class)
            ->onlyMethods(
                [
                    'getFailedBillers',
                    'checkIfThreeDWasUsed'
                ]
            )
            ->setConstructorArgs(
                [
                    $purchaseProcessHandlerMock,
                    $this->createMock(FailedBillersHttpQueryDTOAssembler::class)
                ]
            )
            ->getMock();
        $handler->method('getFailedBillers')->willReturn($this->createMock(FailedBillers::class));
        $handler->method('checkIfThreeDWasUsed')->willReturn(false);

        $handler->execute(new RetrieveFailedBillersQuery($sessionId));
    }

    /**
     * @test
     * @return void
     * @throws \Throwable
     */
    public function it_should_throw_exception_when_a_invalid_query_is_provided(): void
    {
        $this->expectException(InvalidQueryException::class);

        $handler = new RetrieveFailedBillersQueryHandler(
            $this->createMock(PurchaseProcessHandler::class),
            $this->createMock(FailedBillersHttpQueryDTOAssembler::class)
        );

        $handler->execute($this->createMock(RetrieveItemQuery::class));
    }

    /**
     * @test
     * @return FailedBillersQueryHttpDTO
     */
    public function it_should_call_the_dto_assembler_with_a_failed_billers_vo(): FailedBillersQueryHttpDTO
    {
        $failedBillersMock = $this->createMock(FailedBillers::class);

        $dtoAssembler = $this->getMockBuilder(FailedBillersHttpQueryDTOAssembler::class)
            ->onlyMethods(['assemble'])
            ->getMock();
        $dtoAssembler->expects($this->once())->method('assemble')
            ->with($failedBillersMock)
            ->willReturn($this->createMock(FailedBillersQueryHttpDTO::class));

        $handler = $this->getMockBuilder(RetrieveFailedBillersQueryHandler::class)
            ->onlyMethods(['getFailedBillers'])
            ->setConstructorArgs([$this->createMock(PurchaseProcessHandler::class), $dtoAssembler])
            ->getMock();
        $handler->method('getFailedBillers')->willReturn($failedBillersMock);

        return $handler->execute(new RetrieveFailedBillersQuery($this->faker->uuid));
    }

    /**
     * @test
     * @depends it_should_call_the_dto_assembler_with_a_failed_billers_vo
     * @param FailedBillersQueryHttpDTO $dto The Failed Billers DTO
     * @return void
     */
    public function it_should_return_a_dto_object(FailedBillersQueryHttpDTO $dto): void
    {
        $this->assertInstanceOf(FailedBillersQueryHttpDTO::class, $dto);
    }
}
