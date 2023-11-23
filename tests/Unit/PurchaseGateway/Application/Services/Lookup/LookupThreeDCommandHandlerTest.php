<?php

namespace Tests\Unit\PurchaseGateway\Application\Services\Lookup;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\BI\BILoggerService;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\PurchaseGateway\Application\DTO\Lookup\LookupThreeDDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\Exceptions\MissingRedirectUrlException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionAlreadyProcessedException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionNotFoundException;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\Lookup\LookupThreeDCommand;
use ProBillerNG\PurchaseGateway\Application\Services\Lookup\LookupThreeDCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\Amount;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleSingleChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\Duration;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InitPurchaseInfoNotFoundException;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\NewCCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Purchase;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\CCForBlackListService;
use ProBillerNG\PurchaseGateway\Domain\Services\PostbackService;
use ProBillerNG\PurchaseGateway\Domain\Services\PurchaseService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Repository\InMemory\RedisRepository;
use Tests\UnitTestCase;

class LookupThreeDCommandHandlerTest extends UnitTestCase
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
     * @var LookupThreeDCommand
     */
    private $command;

    /**
     * @var BILoggerService
     */
    private $biServiceMock;

    /** @var InitializedItem|MockObject */
    private $initializedItem;

    /** @var LookupThreeDDTOAssembler|MockObject */
    private $assembler;
    /**
     * @var MockObject|EventIngestionService
     */
    private $eventIngestionSystem;

    /**
     * @var TransactionService
     */
    private $transactionService;

    /**
     * @var MockObject|PostbackService
     */
    private $postbackMock;

    /**
     * @var MockObject|PurchaseService
     */
    private $purchaseService;

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
        $this->purchaseService       = $this->createMock(PurchaseService::class);
        $this->postbackMock          = $this->createMock(PostbackService::class);
        $this->processHandler        = $this->createMock(PurchaseProcessHandler::class);
        $this->biServiceMock         = $this->createMock(BILoggerService::class);
        $this->eventIngestionSystem  = $this->createMock(EventIngestionService::class);
        $this->ccForBlackListService = $this->createMock(CCForBlackListService::class);
        $this->command               = new LookupThreeDCommand(
            $this->createMock(Site::class),
            $this->faker->creditCardNumber,
            '111',
            '03',
            '2030',
            $this->faker->uuid,
            $this->faker->uuid
        );
        $this->transactionService    = $this->createMock(TransactionService::class);

        $this->initializedItem = $this->createMock(InitializedItem::class);
        $this->assembler       = $this->createMock(LookupThreeDDTOAssembler::class);
    }

    /**
     * @test
     * @return void
     * @throws \Throwable
     * @throws \Exception
     */
    public function it_should_call_retrieve_session_on_lookup_process(): void
    {
        $this->purchaseProcess->method('redirectUrl')->willReturn($this->faker->url);
        $this->purchaseProcess->method('isPending')->willReturn(true);
        $purchase = $this->createMock(Purchase::class);
        $this->purchaseProcess->method('purchase')->willReturn($purchase);

        $initItem = $this->createMock(InitializedItem::class);

        $amount   = Amount::create(20);
        $duration = Duration::create(7);

        $chargeInformation = BundleSingleChargeInformation::create($amount, $duration, null);
        $initItem->method('chargeInformation')->willReturn($chargeInformation);

        $this->purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initItem);

        $newCCpaymentInfo = NewCCPaymentInfo::create(
            $this->command->ccNumber(),
            $this->command->cvv(),
            $this->command->expirationMonth(),
            $this->command->expirationYear(),
            'visa'
        );

        $this->purchaseProcess->method('paymentInfo')->willReturn($newCCpaymentInfo);
        $this->processHandler->expects($this->once())->method('load')->willReturn(
            $this->purchaseProcess
        );

        $handler = new LookupThreeDCommandHandler(
            $this->assembler,
            $this->processHandler,
            $this->transactionService,
            $this->biServiceMock,
            $this->createMock(CryptService::class),
            $this->createMock(TokenGenerator::class),
            $this->purchaseService,
            $this->postbackMock,
            $this->eventIngestionSystem,
            $this->createMock(RedisRepository::class),
            $this->ccForBlackListService
        );

        $handler->execute($this->command);
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

        $handler = new LookupThreeDCommandHandler(
            $this->assembler,
            $this->processHandler,
            $this->transactionService,
            $this->biServiceMock,
            $this->createMock(CryptService::class),
            $this->createMock(TokenGenerator::class),
            $this->purchaseService,
            $this->postbackMock,
            $this->eventIngestionSystem,
            $this->createMock(RedisRepository::class),
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

        $handler = new LookupThreeDCommandHandler(
            $this->assembler,
            $this->processHandler,
            $this->transactionService,
            $this->biServiceMock,
            $this->createMock(CryptService::class),
            $this->createMock(TokenGenerator::class),
            $this->purchaseService,
            $this->postbackMock,
            $this->eventIngestionSystem,
            $this->createMock(RedisRepository::class),
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
    public function it_should_throw_exception_missing_redirect_url_on_lookup_process(): void
    {
        $this->expectException(MissingRedirectUrlException::class);

        $this->processHandler->method('load')->willReturn(
            $this->purchaseProcess
        );

        $handler = new LookupThreeDCommandHandler(
            $this->assembler,
            $this->processHandler,
            $this->transactionService,
            $this->biServiceMock,
            $this->createMock(CryptService::class),
            $this->createMock(TokenGenerator::class),
            $this->purchaseService,
            $this->postbackMock,
            $this->eventIngestionSystem,
            $this->createMock(RedisRepository::class),
            $this->ccForBlackListService
        );

        $handler->execute($this->command);
    }
}