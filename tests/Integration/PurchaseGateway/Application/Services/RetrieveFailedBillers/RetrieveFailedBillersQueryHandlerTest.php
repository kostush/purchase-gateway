<?php
declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Application\Services\RetrieveFailedBillers;

use ProBillerNG\PurchaseGateway\Application\DTO\RetrieveFailedBillers\FailedBillersHttpQueryDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\Services\RetrieveFailedBillers\RetrieveFailedBillersQuery;
use ProBillerNG\PurchaseGateway\Application\Services\RetrieveFailedBillers\RetrieveFailedBillersQueryHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InitPurchaseInfoNotFoundException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\NotFoundException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;
use ProBillerNG\PurchaseGateway\Domain\Model\FailedBillers;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItemCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionCollection;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\DatabasePurchaseProcessHandler;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Tests\IntegrationTestCase;

class RetrieveFailedBillersQueryHandlerTest extends IntegrationTestCase
{
    /**
     * @test
     * @throws \Exception
     * @return void
     */
    public function it_should_return_dto_having_the_correct_biller_name(): void
    {
        $purchaseProcessHandlerMock = $this->getMockBuilder(DatabasePurchaseProcessHandler::class)
            ->onlyMethods(['load'])
            ->disableOriginalConstructor()
            ->getMock();
        $purchaseProcessHandlerMock->method('load')->willReturn($this->createMock(PurchaseProcess::class));

        $handler = $this->getMockBuilder(RetrieveFailedBillersQueryHandler::class)
            ->setConstructorArgs([$purchaseProcessHandlerMock, new FailedBillersHttpQueryDTOAssembler()])
            ->onlyMethods(['getFailedBillers'])
            ->getMock();
        $handler->method('getFailedBillers')->willReturn($this->buildFailedBillersObject());

        $dto = $handler->execute(new RetrieveFailedBillersQuery($this->faker->uuid));

        $result = $dto->jsonSerialize();

        $this->assertSame(RocketgateBiller::BILLER_NAME, $result['failedBillers'][0]['billerName']);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public function it_should_throw_exception_given_non_existing_session_id(): void
    {
        $this->expectException(NotFoundException::class);

        $query = new RetrieveFailedBillersQuery($this->faker->uuid);

        $purchaseProcessHandlerMock = $this->getMockBuilder(DatabasePurchaseProcessHandler::class)
            ->onlyMethods(['load'])
            ->disableOriginalConstructor()
            ->getMock();
        $purchaseProcessHandlerMock->method('load')->willThrowException(new InitPurchaseInfoNotFoundException());

        $handler = new RetrieveFailedBillersQueryHandler(
            $purchaseProcessHandlerMock,
            $this->createMock(FailedBillersHttpQueryDTOAssembler::class)
        );

        $handler->execute($query);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public function it_should_throw_exception_given_an_invalid_item_id(): void
    {
        $this->expectException(ValidationException::class);

        $query = new RetrieveFailedBillersQuery('123');

        $purchaseProcessHandlerMock = $this->getMockBuilder(DatabasePurchaseProcessHandler::class)
            ->onlyMethods(['load'])
            ->disableOriginalConstructor()
            ->getMock();
        $purchaseProcessHandlerMock->method('load')->willThrowException(new InvalidUuidStringException());

        $handler = new RetrieveFailedBillersQueryHandler(
            $purchaseProcessHandlerMock,
            $this->createMock(FailedBillersHttpQueryDTOAssembler::class)
        );

        $handler->execute($query);
    }

    /**
     * @return FailedBillers
     * @throws \Exception
     */
    private function buildFailedBillersObject(): FailedBillers
    {
        $transactionCollection = new TransactionCollection();

        $transactionMock = $this->getMockBuilder(Transaction::class)
            ->onlyMethods(['billerName', 'state'])
            ->disableOriginalConstructor()
            ->getMock();
        $transactionMock->method('billerName')->willReturn(RocketgateBiller::BILLER_NAME);
        $transactionMock->method('state')->willReturn(Transaction::STATUS_ABORTED);

        $transactionCollection->add($transactionMock);

        $initializedItem = $this->getMockBuilder(InitializedItem::class)
            ->onlyMethods(['transactionCollection'])
            ->disableOriginalConstructor()
            ->getMock();
        $initializedItem->method('transactionCollection')->willReturn($transactionCollection);

        $initializedItemCollection = new InitializedItemCollection();
        $initializedItemCollection->add($initializedItem);

        return FailedBillers::createFromInitializedItemCollection($initializedItemCollection);
    }
}
