<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\Authenticate;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\BI\BILoggerService;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\PurchaseGateway\Application\DTO\Authenticate\AuthenticateThreeDQueryDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\Exceptions\MissingRedirectUrlException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionAlreadyProcessedException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionNotFoundException;
use ProBillerNG\PurchaseGateway\Application\Services\Authenticate\AuthenticateThreeDQuery;
use ProBillerNG\PurchaseGateway\Application\Services\Authenticate\AuthenticateThreeDQueryHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InitPurchaseInfoNotFoundException;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\Purchase;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionCollection;
use Tests\UnitTestCase;

class AuthenticateThreeDQueryHandlerTest extends UnitTestCase
{
    /**
     * @var PurchaseProcess
     */
    private $purchaseProcess;

    /**
     * @var PurchaseProcessHandler
     */
    private $processHandler;
    /**
     * @var AuthenticateThreeDQuery
     */
    private $query;

    /**
     * @var BILoggerService
     */
    private $biServiceMock;

    /** @var InitializedItem|MockObject */
    private $initializedItem;

    /** @var AuthenticateThreeDQueryDTOAssembler|MockObject */
    private $assembler;
    /**
     * @var MockObject|EventIngestionService
     */
    private $eventIngestionSystem;

    /**
     * @return void
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->purchaseProcess = $this->createMock(PurchaseProcess::class);
        $purchase              = $this->createMock(Purchase::class);
        $this->purchaseProcess->method('purchase')->willReturn($purchase);

        $this->processHandler = $this->createMock(PurchaseProcessHandler::class);
        $this->biServiceMock  = $this->createMock(BILoggerService::class);
        $this->eventIngestionSystem  = $this->createMock(EventIngestionService::class);
        $this->query          = new AuthenticateThreeDQuery($this->faker->uuid);


        $transaction = $this->createMock(Transaction::class);

        $transaction->method('pareq')->willReturn('simulated pareq');
        $transaction->method('acs')->willReturn($this->faker->url);

        $collection = new TransactionCollection();
        $collection->add($transaction);

        $this->initializedItem = $this->createMock(InitializedItem::class);
        $this->initializedItem->method('transactionCollection')->willReturn($collection);

        $this->assembler = $this->createMock(AuthenticateThreeDQueryDTOAssembler::class);
    }

    /**
     * @test
     * @throws \Exception
     * @return void
     */
    public function it_should_call_retrieve_session_on_authenticate_process(): void
    {
        $this->purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($this->initializedItem);

        $this->purchaseProcess->method('redirectUrl')->willReturn($this->faker->url);
        $this->purchaseProcess->method('isPending')->willReturn(true);
        $this->processHandler->expects($this->once())->method('load')->willReturn(
            $this->purchaseProcess
        );

        $handler = new AuthenticateThreeDQueryHandler(
            $this->assembler,
            $this->processHandler,
            $this->biServiceMock,
            $this->eventIngestionSystem
        );

        $handler->execute($this->query);
    }

    /**
     * @test
     * @return void
     * @throws \Throwable
     */
    public function it_should_throw_session_already_processed_exception_when_state_not_pending(): void
    {
        $this->expectException(SessionAlreadyProcessedException::class);

        $this->purchaseProcess->method('isPending')->willReturn(false);
        $this->purchaseProcess->method('redirectUrl')->willReturn($this->faker->url);

        $this->purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($this->initializedItem);

        $this->processHandler->method('load')->willReturn(
            $this->purchaseProcess
        );

        $handler = new AuthenticateThreeDQueryHandler(
            $this->assembler,
            $this->processHandler,
            $this->biServiceMock,
            $this->eventIngestionSystem
        );

        $handler->execute($this->query);
    }

    /**
     * @test
     * @throws \Exception
     * @return void
     */
    public function it_should_throw_exception_given_non_existing_process_purchase_session(): void
    {
        $this->expectException(SessionNotFoundException::class);

        $this->processHandler->method('load')->willThrowException(new InitPurchaseInfoNotFoundException());

        $handler = new AuthenticateThreeDQueryHandler(
            $this->assembler,
            $this->processHandler,
            $this->biServiceMock,
            $this->eventIngestionSystem
        );

        $handler->execute($this->query);
    }

    /**
     * @test
     * @throws \Exception
     * @return void
     */
    public function it_should_throw_exception_missing_redirect_url_on_purchase_process(): void
    {
        $this->expectException(MissingRedirectUrlException::class);

        $this->processHandler->method('load')->willReturn(
            $this->purchaseProcess
        );

        $handler = new AuthenticateThreeDQueryHandler(
            $this->assembler,
            $this->processHandler,
            $this->biServiceMock,
            $this->eventIngestionSystem
        );

        $handler->execute($this->query);
    }
}
