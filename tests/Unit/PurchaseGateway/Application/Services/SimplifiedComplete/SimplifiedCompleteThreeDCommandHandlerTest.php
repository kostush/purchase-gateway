<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\SimplifiedComplete;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\BI\BILoggerService;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\PurchaseGateway\Application\DTO\Complete\CompleteThreeDCommandDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\Exceptions\MissingMandatoryQueryParamsForCompleteException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\MissingRedirectUrlException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionAlreadyProcessedException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionNotFoundException;
use ProBillerNG\PurchaseGateway\Application\Services\Complete\CompleteThreeDCommand;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Application\Services\SimplifiedComplete\SimplifiedCompleteThreeDCommand;
use ProBillerNG\PurchaseGateway\Application\Services\SimplifiedComplete\SimplifiedCompleteThreeDCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\Amount;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleSingleChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\CardInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;
use ProBillerNG\PurchaseGateway\Domain\Model\Duration;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InitPurchaseInfoNotFoundException;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\InMemoryRepository;
use ProBillerNG\PurchaseGateway\Domain\Model\Purchase;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Valid;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Domain\Services\PostbackService;
use ProBillerNG\PurchaseGateway\Domain\Services\PurchaseService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\UnableToCompleteThreeDTransactionException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\NewCCTransactionInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionTranslatingService;
use Tests\UnitTestCase;
use Throwable;

class SimplifiedCompleteThreeDCommandHandlerTest extends UnitTestCase
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
     * @var CompleteThreeDCommand
     */
    private $command;

    /**
     * @var BILoggerService
     */
    private $biServiceMock;

    /**
     * @var ConfigService
     */
    private $configServiceClient;

    /**
     * @var PurchaseService
     */
    private $purchaseService;

    /**
     * @var TransactionService
     */
    private $transactionService;

    /**
     * @var PostbackService
     */
    private $postbackMock;

    /**
     * @var MockObject|EventIngestionService
     */
    private $eventIngestionSystem;

    /**
     * @var MockObject|InMemoryRepository
     */
    private $redisRepository;

    /**
     * @return void
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        parse_str(
            'flag=17c6f59e222&id=64d98d86-61642f822233e7.53329385&invoiceID=aba9b991-61642f82223498.08058272&hash=4qEW12Qdl5%2FYxkCtRbZ%2FHT%2Bi1NM%3D',
            $queryString
        );

        $this->purchaseProcess = $this->createMock(PurchaseProcess::class);
        $purchase              = $this->createMock(Purchase::class);

        $this->purchaseProcess->method('purchase')->willReturn($purchase);
        $this->purchaseProcess->method('cascade')->willReturn(
            Cascade::create(BillerCollection::buildBillerCollection([new RocketgateBiller()]))
        );
        $this->purchaseProcess->method('sessionId')->willReturn(SessionId::create());
        $this->purchaseProcess->method('state')->willReturn(Valid::create());
        $this->purchaseProcess->method('gatewaySubmitNumber')->willReturn(1);

        $this->processHandler       = $this->createMock(PurchaseProcessHandler::class);
        $this->biServiceMock        = $this->createMock(BILoggerService::class);
        $this->eventIngestionSystem = $this->createMock(EventIngestionService::class);
        $this->postbackMock         = $this->createMock(PostbackService::class);
        $this->command              = new SimplifiedCompleteThreeDCommand(
            $this->faker->uuid,
            $queryString
        );

        $this->configServiceClient = $this->createMock(ConfigService::class);

        $this->configServiceClient->method('getSite')->willReturn($this->createMock(Site::class));

        $this->purchaseService    = $this->createMock(PurchaseService::class);
        $this->transactionService = $this->createMock(TransactionService::class);

        $transactionInfo = $this->createMock(NewCCTransactionInformation::class);
        $transactionInfo->method('last4')->willReturn('1234');
        $transactionInfo->method('first6')->willReturn('123456');
        $this->transactionService->method('attemptSimplifiedCompleteThreeDTransaction')
            ->willReturn($transactionInfo);

        $this->redisRepository = $this->createMock(InMemoryRepository::class);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws Throwable
     */
    public function it_should_throw_an_exception_when_redirect_url_is_missing(): void
    {
        $this->expectException(MissingRedirectUrlException::class);

        $this->processHandler->expects(self::once())->method('load')->willReturn($this->purchaseProcess);
        $this->purchaseProcess->method('redirectUrl')->willReturn(null);

        $handler = new SimplifiedCompleteThreeDCommandHandler(
            $this->createMock(CompleteThreeDCommandDTOAssembler::class),
            $this->transactionService,
            $this->processHandler,
            $this->configServiceClient,
            $this->purchaseService,
            $this->postbackMock,
            $this->biServiceMock,
            $this->eventIngestionSystem,
            $this->redisRepository
        );

        $handler->execute($this->command);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws Throwable
     */
    public function it_should_throw_an_exception_when_invoice_id_is_missing(): void
    {
        $this->expectException(MissingMandatoryQueryParamsForCompleteException::class);

        $this->processHandler->expects(self::once())->method('load')->willReturn($this->purchaseProcess);
        $this->purchaseProcess->method('redirectUrl')->willReturn($this->faker->url);


        parse_str(
            'flag=17c6f59e222&id=64d98d86-61642f822233e7.53329385&hash=4qEW12Qdl5%2FYxkCtRbZ%2FHT%2Bi1NM%3D',
            $queryString
        );

        $command = new SimplifiedCompleteThreeDCommand(
            $this->faker->uuid,
            $queryString
        );

        $handler = new SimplifiedCompleteThreeDCommandHandler(
            $this->createMock(CompleteThreeDCommandDTOAssembler::class),
            $this->transactionService,
            $this->processHandler,
            $this->configServiceClient,
            $this->purchaseService,
            $this->postbackMock,
            $this->biServiceMock,
            $this->eventIngestionSystem,
            $this->redisRepository
        );

        $handler->execute($command);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws Throwable
     */
    public function it_should_throw_an_exception_when_hash_is_missing(): void
    {
        $this->expectException(MissingMandatoryQueryParamsForCompleteException::class);

        $this->processHandler->expects(self::once())->method('load')->willReturn($this->purchaseProcess);
        $this->purchaseProcess->method('redirectUrl')->willReturn($this->faker->url);


        parse_str(
            'flag=17c6f59e222&id=64d98d86-61642f822233e7.53329385&invoiceId=aba9b991-61642f82223498.08058272',
            $queryString
        );

        $command = new SimplifiedCompleteThreeDCommand(
            $this->faker->uuid,
            $queryString
        );

        $handler = new SimplifiedCompleteThreeDCommandHandler(
            $this->createMock(CompleteThreeDCommandDTOAssembler::class),
            $this->transactionService,
            $this->processHandler,
            $this->configServiceClient,
            $this->purchaseService,
            $this->postbackMock,
            $this->biServiceMock,
            $this->eventIngestionSystem,
            $this->redisRepository
        );

        $handler->execute($command);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws Throwable
     */
    public function it_should_throw_an_exception_on_attempting_to_simplify_complete_a_three_d_transaction(): void
    {
        $this->expectException(UnableToCompleteThreeDTransactionException::class);

        $mainPurchase = $this->createMock(InitializedItem::class);
        $mainPurchase->method('lastTransactionId')
            ->willReturn(TransactionId::createFromString($this->faker->uuid));

        $transaction = Transaction::create(
            TransactionId::createFromString($this->faker->uuid),
            Transaction::STATUS_PENDING,
            RocketgateBiller::BILLER_NAME
        );

        $mainPurchase->method('lastTransaction')->willReturn($transaction);

        $transactionTranslatingService = $this->createMock(TransactionTranslatingService::class);
        $tokenGenerator                = $this->createMock(TokenGenerator::class);
        $cryptService                  = $this->createMock(CryptService::class);
        $transactionTranslatingService->method("performSimplifiedCompleteThreeDTransaction")->willReturn(
            Transaction::create(null, Transaction::STATUS_ABORTED, RocketgateBiller::BILLER_NAME)
        );

        $transactionService = new TransactionService(
            $transactionTranslatingService,
            $tokenGenerator,
            $cryptService
        );

        $transactionService->attemptSimplifiedCompleteThreeDTransaction(
            $mainPurchase,
            [],
            $this->createMock(Site::class),
            null,
            $this->createMock(UserInfo::class),
            $this->createMock(SessionId::class),
            '',
            null
        );
    }

    /**
     * @test
     * @return void
     * @throws Throwable
     */
    public function it_should_pass_a_complete_dto_object_to_the_dto_assembler_when_session_was_provided(): void
    {
        $initItem = $this->createMock(InitializedItem::class);
        $amount   = Amount::create(20);
        $duration = Duration::create(7);

        $chargeInformation = BundleSingleChargeInformation::create($amount, $duration, null);
        $initItem->method('chargeInformation')->willReturn($chargeInformation);
        $firstTransactionId = TransactionId::createFromString($this->faker->uuid);
        $firstTransaction   = $this->createMock(Transaction::class);
        $firstTransaction->method('transactionId')->willReturn($firstTransactionId);
        $firstTransaction->method('billerName')->willReturn('rocketgate');
        $transactionCollection = new TransactionCollection();
        $transactionCollection->add($firstTransaction);
        $initItem->method('transactionCollection')->willReturn($transactionCollection);

        $this->purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initItem);
        $this->purchaseProcess->method('redirectUrl')->willReturn($this->faker->url);
        $this->processHandler->method('load')->willReturn(
            $this->purchaseProcess
        );

        $this->purchaseProcess->method('paymentInfo')->willReturn(
            CardInfo::create('123456', '1234', '11', '2025', null)
        );

        $assembler = $this->createMock(CompleteThreeDCommandDTOAssembler::class);
        $assembler->expects($this->once())
            ->method('assemble')
            ->with($this->purchaseProcess);

        $handler = new SimplifiedCompleteThreeDCommandHandler(
            $assembler,
            $this->transactionService,
            $this->processHandler,
            $this->configServiceClient,
            $this->purchaseService,
            $this->postbackMock,
            $this->biServiceMock,
            $this->eventIngestionSystem,
            $this->redisRepository
        );

        $handler->execute($this->command);
    }

    /**
     * @test
     * @return void
     * @throws Throwable
     * @throws Exception
     */
    public function it_should_throw_exception_given_non_existing_process_purchase_session(): void
    {
        $this->expectException(SessionNotFoundException::class);

        $this->processHandler->method('load')->willThrowException(new InitPurchaseInfoNotFoundException());

        $assembler = $this->createMock(CompleteThreeDCommandDTOAssembler::class);

        $handler = new SimplifiedCompleteThreeDCommandHandler(
            $assembler,
            $this->transactionService,
            $this->processHandler,
            $this->configServiceClient,
            $this->purchaseService,
            $this->postbackMock,
            $this->biServiceMock,
            $this->eventIngestionSystem,
            $this->redisRepository
        );

        $handler->execute($this->command);
    }

    /**
     * @test
     * @return void
     * @throws Throwable
     * @throws Exception
     */
    public function it_should_throw_exception_when_missing_redirect_url_in_the_session_object(): void
    {
        $this->expectException(MissingRedirectUrlException::class);

        $this->processHandler->method('load')->willReturn($this->purchaseProcess);

        $assembler = $this->createMock(CompleteThreeDCommandDTOAssembler::class);

        $handler = new SimplifiedCompleteThreeDCommandHandler(
            $assembler,
            $this->transactionService,
            $this->processHandler,
            $this->configServiceClient,
            $this->purchaseService,
            $this->postbackMock,
            $this->biServiceMock,
            $this->eventIngestionSystem,
            $this->redisRepository
        );

        $result = $handler->execute($this->command);

        $result = $result->jsonSerialize();

        $this->assertInstanceOf(SessionAlreadyProcessedException::class, $result['error']);
    }
}
