<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\Complete;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\BI\BILoggerService;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\PurchaseGateway\Application\DTO\Complete\CompleteThreeDCommandDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\Exceptions\MissingParesAndMdException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\MissingRedirectUrlException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionAlreadyProcessedException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionNotFoundException;
use ProBillerNG\PurchaseGateway\Application\Services\Complete\CompleteThreeDCommand;
use ProBillerNG\PurchaseGateway\Application\Services\Complete\CompleteThreeDCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\Amount;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleSingleChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\CardInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;
use ProBillerNG\PurchaseGateway\Domain\Model\Duration;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InitPurchaseInfoNotFoundException;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\Purchase;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Domain\Services\CCForBlackListService;
use ProBillerNG\PurchaseGateway\Domain\Services\PostbackService;
use ProBillerNG\PurchaseGateway\Domain\Services\PurchaseService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\UnableToCompleteThreeDTransactionException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\NewCCTransactionInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionTranslatingService;
use Tests\UnitTestCase;

class CompleteThreeDCommandHandlerTest extends UnitTestCase
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

    /** @var ConfigService */
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
     * @var MockObject|CCForBlackListService
     */
    private $ccForBlackListService;

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
        $this->purchaseProcess->method('cascade')->willReturn(
            Cascade::create(BillerCollection::buildBillerCollection([new RocketgateBiller()]))
        );
        $this->processHandler        = $this->createMock(PurchaseProcessHandler::class);
        $this->biServiceMock         = $this->createMock(BILoggerService::class);
        $this->eventIngestionSystem  = $this->createMock(EventIngestionService::class);
        $this->postbackMock          = $this->createMock(PostbackService::class);
        $this->ccForBlackListService = $this->createMock(CCForBlackListService::class);
        $this->command               = new CompleteThreeDCommand($this->faker->uuid, 'pares', 'md');

        $this->configServiceClient = $this->createMock(ConfigService::class);

        $this->configServiceClient->method('getSite')->willReturn($this->createMock(Site::class));

        $this->purchaseService    = $this->createMock(PurchaseService::class);
        $this->transactionService = $this->createMock(TransactionService::class);

        $transactionInfo = $this->createMock(NewCCTransactionInformation::class);
        $transactionInfo->method('last4')->willReturn('1234');
        $transactionInfo->method('first6')->willReturn('123456');
        $this->transactionService->method('attemptCompleteThreeDTransaction')
            ->willReturn($transactionInfo);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public function it_should_throw_an_exception_on_attempting_to_complete_an_three_d_transaction(): void
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
        $transaction->setThreeDVersion(1);
        $mainPurchase->method('lastTransaction')
            ->willReturn($transaction);

        $transactionTranslatingService = $this->createMock(TransactionTranslatingService::class);
        $tokenGenerator                = $this->createMock(TokenGenerator::class);
        $cryptService                  = $this->createMock(CryptService::class);
        $transactionTranslatingService->method("performCompleteThreeDTransaction")->willReturn(
            Transaction::create(null, Transaction::STATUS_ABORTED, RocketgateBiller::BILLER_NAME)
        );

        $transactionService = new TransactionService(
            $transactionTranslatingService,
            $tokenGenerator,
            $cryptService
        );
        $transactionService->attemptCompleteThreeDTransaction(
            $mainPurchase,
            [],
            $this->createMock(Site::class),
            null,
            $this->createMock(UserInfo::class),
            $this->createMock(SessionId::class),
            null,
            null,
            null
        );
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     * @throws \Throwable
     */
    public function it_should_throw_session_already_processed_exception(): void
    {
        $this->expectException(SessionAlreadyProcessedException::class);

        $this->purchaseProcess->method('isThreeDAuthenticated')->willReturn(false);
        $this->purchaseProcess->method('redirectUrl')->willReturn($this->faker->url);
        $this->processHandler->expects($this->once())->method('load')->willReturn(
            $this->purchaseProcess
        );

        $handler = new CompleteThreeDCommandHandler(
            $this->createMock(CompleteThreeDCommandDTOAssembler::class),
            $this->transactionService,
            $this->processHandler,
            $this->configServiceClient,
            $this->purchaseService,
            $this->postbackMock,
            $this->biServiceMock,
            $this->eventIngestionSystem,
            $this->ccForBlackListService
        );

        $handler->execute($this->command);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     * @throws \Throwable
     */
    public function it_should_throw_missing_pares_exception(): void
    {
        $this->expectException(MissingParesAndMdException::class);

        $this->purchaseProcess->method('isThreeDAuthenticated')->willReturn(true);
        $this->purchaseProcess->method('redirectUrl')->willReturn($this->faker->url);
        $this->processHandler->expects($this->once())->method('load')->willReturn(
            $this->purchaseProcess
        );

        $handler = new CompleteThreeDCommandHandler(
            $this->createMock(CompleteThreeDCommandDTOAssembler::class),
            $this->transactionService,
            $this->processHandler,
            $this->configServiceClient,
            $this->purchaseService,
            $this->postbackMock,
            $this->biServiceMock,
            $this->eventIngestionSystem,
            $this->ccForBlackListService
        );

        $command = new CompleteThreeDCommand($this->faker->uuid, '', null);
        $handler->execute($command);
    }

    /**
     * @test
     * @return void
     * @throws \Throwable
     */
    public function it_should_pass_a_complete_dto_object_to_the_dto_assembler_when_session_was_provided(): void
    {
        $this->purchaseProcess->method('isThreeDAuthenticated')->willReturn(true);

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


        $handler = new CompleteThreeDCommandHandler(
            $assembler,
            $this->transactionService,
            $this->processHandler,
            $this->configServiceClient,
            $this->purchaseService,
            $this->postbackMock,
            $this->biServiceMock,
            $this->eventIngestionSystem,
            $this->ccForBlackListService
        );

        $handler->execute($this->command);
    }

    /**
     * @test
     * @return void
     * @throws \Throwable
     * @throws \Exception
     */
    public function it_should_throw_exception_given_non_existing_process_purchase_session(): void
    {
        $this->expectException(SessionNotFoundException::class);

        $this->processHandler->method('load')->willThrowException(new InitPurchaseInfoNotFoundException());

        $assembler = $this->createMock(CompleteThreeDCommandDTOAssembler::class);

        $handler = new CompleteThreeDCommandHandler(
            $assembler,
            $this->transactionService,
            $this->processHandler,
            $this->configServiceClient,
            $this->purchaseService,
            $this->postbackMock,
            $this->biServiceMock,
            $this->eventIngestionSystem,
            $this->ccForBlackListService
        );

        $handler->execute($this->command);
    }

    /**
     * @test
     * @return void
     * @throws \Throwable
     * @throws \Exception
     */
    public function it_should_throw_exception_when_missing_redirect_url_in_the_session_object(): void
    {
        $this->expectException(MissingRedirectUrlException::class);

        $this->processHandler->method('load')->willReturn(
            $this->purchaseProcess
        );

        $assembler = $this->createMock(CompleteThreeDCommandDTOAssembler::class);

        $handler = new CompleteThreeDCommandHandler(
            $assembler,
            $this->transactionService,
            $this->processHandler,
            $this->configServiceClient,
            $this->purchaseService,
            $this->postbackMock,
            $this->biServiceMock,
            $this->eventIngestionSystem,
            $this->ccForBlackListService
        );

        $result = $handler->execute($this->command);

        $result = $result->jsonSerialize();

        $this->assertInstanceOf(SessionAlreadyProcessedException::class, $result['error']);
    }
}
