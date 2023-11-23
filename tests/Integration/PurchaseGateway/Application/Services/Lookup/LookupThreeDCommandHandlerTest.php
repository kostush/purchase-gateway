<?php

namespace Tests\Integration\PurchaseGateway\Application\Services\Lookup;

use ProBillerNG\BI\BILoggerService;
use ProBillerNG\Crypt\Sodium\PrivateKeyConfig;
use ProBillerNG\Crypt\Sodium\PrivateKeyCypher;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\PurchaseGateway\Application\DTO\Lookup\LookupThreeDDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\Lookup\LookupThreeDHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionAlreadyProcessedException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionNotFoundException;
use ProBillerNG\PurchaseGateway\Application\Services\Lookup\LookupThreeDCommand;
use ProBillerNG\PurchaseGateway\Application\Services\Lookup\LookupThreeDCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\Amount;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleSingleChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\Duration;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\DuplicatedPurchaseProcessRequestException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InitPurchaseInfoNotFoundException;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\NewCCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Purchase;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processing;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionCollection;
use ProBillerNG\PurchaseGateway\Domain\Services\CCForBlackListService;
use ProBillerNG\PurchaseGateway\Domain\Services\PostbackService;
use ProBillerNG\PurchaseGateway\Domain\Services\PurchaseService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\JsonWebTokenGenerator;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\SodiumCryptService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Repository\InMemory\RedisRepository;
use Tests\IntegrationTestCase;

class LookupThreeDCommandHandlerTest extends IntegrationTestCase
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
     * @var LookupThreeDCommand
     */
    private $command;

    /**
     * @var BILoggerService
     */
    private $biServiceMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CCForBlackListService
     */
    private $ccForBlackListService;

    /**
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->purchaseProcess       = $this->createMock(PurchaseProcess::class);
        $this->processHandler        = $this->createMock(PurchaseProcessHandler::class);
        $this->tokenGenerator        = new JsonWebTokenGenerator();
        $this->biServiceMock         = $this->createMock(BILoggerService::class);
        $this->ccForBlackListService = $this->createMock(CCForBlackListService::class);

        $this->cryptService = new SodiumCryptService(
            new PrivateKeyCypher(
                new PrivateKeyConfig(
                    env('APP_CRYPT_KEY')
                )
            )
        );

        $this->command = new LookupThreeDCommand(
            $this->createMock(Site::class),
            $this->faker->creditCardNumber,
            '111',
            '03',
            '2030',
            $this->faker->uuid,
            $this->faker->uuid
        );
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     * @throws \Throwable
     */
    public function it_should_successfully_execute_command_and_return_authentication_response()
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

        $transaction = $this->createMock(Transaction::class);

        $transaction->method('threeDStepUpJwt')->willReturn('stepUp jwt');
        $transaction->method('threeDStepUpUrl')->willReturn($this->faker->url);
        $transaction->method('threeDVersion')->willReturn(2);
        $collection = new TransactionCollection();
        $collection->add($transaction);

        $initItem = $this->createMock(InitializedItem::class);
        $initItem->method('transactionCollection')->willReturn($collection);
        $amount   = Amount::create(20);
        $duration = Duration::create(7);

        $chargeInformation = BundleSingleChargeInformation::create($amount, $duration, null);
        $initItem->method('chargeInformation')->willReturn($chargeInformation);

        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initItem);
        $newCCpaymentInfo = NewCCPaymentInfo::create(
            $this->command->ccNumber(),
            $this->command->cvv(),
            $this->command->expirationMonth(),
            $this->command->expirationYear(),
            'visa'
        );

        $purchaseProcess->method('paymentInfo')->willReturn($newCCpaymentInfo);

        $handler = new LookupThreeDCommandHandler(
            $this->createMock(LookupThreeDDTOAssembler::class),
            $processHandler,
            $this->createMock(TransactionService::class),
            $this->biServiceMock,
            $this->cryptService,
            $this->tokenGenerator,
            $this->createMock(PurchaseService::class),
            $this->createMock(PostbackService::class),
            $this->createMock(EventIngestionService::class),
            $this->createMock(RedisRepository::class),
            $this->ccForBlackListService
        );

        $result = $handler->execute($this->command);

        $this->assertInstanceOf(LookupThreeDHttpDTO::class, $result);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     * @throws \Throwable
     */
    public function it_should_throw_exception_given_already_processed_purchase_session(): void
    {
        $this->expectException(SessionAlreadyProcessedException::class);

        $this->processHandler->method('load')->willReturn(
            $this->purchaseProcess
        );

        $this->purchaseProcess->method('redirectUrl')->willReturn('dummy.url');

        $handler = new LookupThreeDCommandHandler(
            $this->createMock(LookupThreeDDTOAssembler::class),
            $this->processHandler,
            $this->createMock(TransactionService::class),
            $this->biServiceMock,
            $this->cryptService,
            $this->tokenGenerator,
            $this->createMock(PurchaseService::class),
            $this->createMock(PostbackService::class),
            $this->createMock(EventIngestionService::class),
            $this->createMock(RedisRepository::class),
            $this->ccForBlackListService
        );

        $handler->execute($this->command);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_exception_given_non_existing_process_purchase_session(): void
    {
        $this->expectException(SessionNotFoundException::class);

        $this->processHandler->method('load')->willThrowException(new InitPurchaseInfoNotFoundException());

        $handler = new LookupThreeDCommandHandler(
            $this->createMock(LookupThreeDDTOAssembler::class),
            $this->processHandler,
            $this->createMock(TransactionService::class),
            $this->biServiceMock,
            $this->cryptService,
            $this->tokenGenerator,
            $this->createMock(PurchaseService::class),
            $this->createMock(PostbackService::class),
            $this->createMock(EventIngestionService::class),
            $this->createMock(RedisRepository::class),
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
    public function it_should_leave_purchase_in_pending_state_if_biller_switches_to_3ds_instead_3ds2()
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

        $transaction = $this->createMock(Transaction::class);
        $transaction->method('pareq')->willReturn('simulated pareq');
        $transaction->method('acs')->willReturn($this->faker->url);
        $transaction->method('isPending')->willReturn(true);

        $transactionService = $this->createMock(TransactionService::class);
        $transactionService->method('lookupTransaction')->willReturn($transaction);

        $collection = new TransactionCollection();
        $collection->add($transaction);

        $initItem = $this->createMock(InitializedItem::class);
        $initItem->method('transactionCollection')->willReturn($collection);
        $amount   = Amount::create(20);
        $duration = Duration::create(7);

        $chargeInformation = BundleSingleChargeInformation::create($amount, $duration, null);
        $initItem->method('chargeInformation')->willReturn($chargeInformation);

        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initItem);
        $newCCpaymentInfo = NewCCPaymentInfo::create(
            $this->command->ccNumber(),
            $this->command->cvv(),
            $this->command->expirationMonth(),
            $this->command->expirationYear(),
            'visa'
        );

        $purchaseProcess->method('paymentInfo')->willReturn($newCCpaymentInfo);

        $handler = new LookupThreeDCommandHandler(
            $this->createMock(LookupThreeDDTOAssembler::class),
            $processHandler,
            $transactionService,
            $this->biServiceMock,
            $this->cryptService,
            $this->tokenGenerator,
            $this->createMock(PurchaseService::class),
            $this->createMock(PostbackService::class),
            $this->createMock(EventIngestionService::class),
            $this->createMock(RedisRepository::class),
            $this->ccForBlackListService
        );

        $result = $handler->execute($this->command);

        $this->assertInstanceOf(LookupThreeDHttpDTO::class, $result);
    }

    /**
     * @test
     *
     * @throws Exception
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidCommandException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @throws \ProBillerNG\PurchaseGateway\Exception
     * @throws \Throwable
     */
    public function it_should_throw_exception_when_duplicate_process_request_is_sent_for_processing()
    {
        $this->expectException(DuplicatedPurchaseProcessRequestException::class);

        $redisRepositoryMock = $this->createMock(RedisRepository::class);
        $redisRepositoryMock->method('retrievePurchaseStatus')->willReturn(Processing::name());

        $command = $this->createMock(LookupThreeDCommand::class);
        $command->method('sessionId')->willReturn($this->faker->uuid);

        //$this->purchaseProcess->method('redirectUrl')->willReturn('dummy.url');

        $handler = new LookupThreeDCommandHandler(
            $this->createMock(LookupThreeDDTOAssembler::class),
            $this->processHandler,
            $this->createMock(TransactionService::class),
            $this->biServiceMock,
            $this->cryptService,
            $this->tokenGenerator,
            $this->createMock(PurchaseService::class),
            $this->createMock(PostbackService::class),
            $this->createMock(EventIngestionService::class),
            $redisRepositoryMock,
            $this->ccForBlackListService
        );

        $handler->execute($this->command);
    }
}
