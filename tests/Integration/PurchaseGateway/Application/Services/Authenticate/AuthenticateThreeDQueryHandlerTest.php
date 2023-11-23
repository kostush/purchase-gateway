<?php

namespace Tests\Integration\PurchaseGateway\Application\Services\Authenticate;

use ProBillerNG\BI\BILoggerService;
use ProBillerNG\Crypt\Sodium\PrivateKeyConfig;
use ProBillerNG\Crypt\Sodium\PrivateKeyCypher;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\PurchaseGateway\Application\DTO\Authenticate\AuthenticateThreeDHttpDTO;
use ProBillerNG\PurchaseGateway\Application\DTO\Authenticate\AuthenticateThreeDQueryDTOAssembler;
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
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\JsonWebTokenGenerator;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\SodiumCryptService;
use Tests\IntegrationTestCase;

class AuthenticateThreeDQueryHandlerTest extends IntegrationTestCase
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
     * @var JsonWebTokenGenerator
     */
    private $tokenGenerator;

    /**
     * @var SodiumCryptService
     */
    private $cryptService;

    /**
     * @var AuthenticateThreeDQuery
     */
    private $query;

    /**
     * @var BILoggerService
     */
    private $biServiceMock;

    /**
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->purchaseProcess = $this->createMock(PurchaseProcess::class);
        $this->processHandler  = $this->createMock(PurchaseProcessHandler::class);
        $this->tokenGenerator  = new JsonWebTokenGenerator();
        $this->biServiceMock   = $this->createMock(BILoggerService::class);

        $this->cryptService = new SodiumCryptService(
            new PrivateKeyCypher(
                new PrivateKeyConfig(
                    env('APP_CRYPT_KEY')
                )
            )
        );

        $this->query = new AuthenticateThreeDQuery($this->faker->uuid);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_successfully_execute_query_and_return_authentication_response()
    {
        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $purchase        = $this->createMock(Purchase::class);
        $purchaseProcess->method('purchase')->willReturn($purchase);
        $purchaseProcess->method('isPending')->willReturn(true);
        $purchaseProcess->method('redirectUrl')->willReturn($this->faker->url);

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->expects($this->once())->method('load')->willReturn(
            $purchaseProcess
        );

        $query = new AuthenticateThreeDQuery($this->faker->uuid);

        $transaction = $this->createMock(Transaction::class);

        $transaction->method('pareq')->willReturn('simulated pareq');
        $transaction->method('acs')->willReturn($this->faker->url);
        $collection = new TransactionCollection();
        $collection->add($transaction);

        $initItem = $this->createMock(InitializedItem::class);
        $initItem->method('transactionCollection')->willReturn($collection);
        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initItem);

        $handler = new AuthenticateThreeDQueryHandler(
            new AuthenticateThreeDQueryDTOAssembler($this->cryptService, $this->tokenGenerator),
            $processHandler,
            $this->biServiceMock,
            $this->createMock(EventIngestionService::class)
        );

        /** @var AuthenticateThreeDQueryHandler $result */
        $result = $handler->execute($query);

        $this->assertInstanceOf(AuthenticateThreeDHttpDTO::class, $result);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_exception_given_already_processed_purchase_session(): void
    {
        $this->expectException(SessionAlreadyProcessedException::class);

        $this->processHandler->method('load')->willReturn(
            $this->purchaseProcess
        );

        $this->purchaseProcess->method('redirectUrl')->willReturn('dummy.url');

        $handler = new AuthenticateThreeDQueryHandler(
            $this->createMock(AuthenticateThreeDQueryDTOAssembler::class),
            $this->processHandler,
            $this->biServiceMock,
            $this->createMock(EventIngestionService::class)
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
            $this->createMock(AuthenticateThreeDQueryDTOAssembler::class),
            $this->processHandler,
            $this->biServiceMock,
            $this->createMock(EventIngestionService::class)
        );

        $handler->execute($this->query);
    }
}
