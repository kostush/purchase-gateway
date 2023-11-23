<?php

namespace Tests\Integration\PurchaseGateway\Application\Services\ThirdPartyPostback;

use ProBillerNG\BI\BILoggerService;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\PurchaseGateway\Application\DTO\ThirdPartyPostback\ThirdPartyPostbackCommandDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\ThirdPartyPostback\ThirdPartyPostbackHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Exceptions\TransactionAlreadyProcessedException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\TransactionNotFoundException;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyPostback\ThirdPartyPostbackCommand;
use ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyPostback\ThirdPartyPostbackCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyPostback\ThirdPartyPostbackCommandHandlerFactory;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\Amount;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleSingleChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;
use ProBillerNG\PurchaseGateway\Domain\Model\Duration;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\CCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processed;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\PostbackService;
use ProBillerNG\PurchaseGateway\Domain\Services\PurchaseService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Domain\Services\UserInfoService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\EpochBillerInteraction;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\EpochCCRetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\TransactionInformation;
use Tests\IntegrationTestCase;

class ThirdPartyPostbackCommandHandlerTest extends IntegrationTestCase
{
    /**
     * @var ThirdPartyPostbackCommand
     */
    private $command;

    /**
     * @return void
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->command = new ThirdPartyPostbackCommand(
            $this->faker->uuid,
            ['ngTransactionId' => $this->faker->uuid],
            ThirdPartyPostbackCommandHandlerFactory::CHARGE
        );
    }

    /**
     * @test
     * @return array
     * @throws \Exception
     */
    public function it_should_return_a_dto_when_a_valid_command_is_provided(): array
    {
        $transaction = $this->createMock(Transaction::class);
        $transaction->method('transactionId')->willReturn(TransactionId::create());
        $transaction->method('state')->willReturn(Transaction::STATUS_PENDING);
        $transaction->method('billerName')->willReturn(EpochBiller::BILLER_NAME);

        $collection = new TransactionCollection();
        $collection->add($transaction);

        $chargeInformation = BundleSingleChargeInformation::create(Amount::create(1), Duration::create(30));

        $initItem = $this->createMock(InitializedItem::class);
        $initItem->method('transactionCollection')->willReturn($collection);
        $initItem->method('wasItemPurchasePending')->willReturn(true);
        $initItem->method('lastTransaction')->willReturn($transaction);
        $initItem->method('chargeInformation')->willReturn($chargeInformation);

        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initItem);
        $purchaseProcess->method('checkIfTransactionIdExist')->willReturn(true);
        $purchaseProcess->method('sessionId')->willReturn(SessionId::create());
        $purchaseProcess->method('isProcessed')->willReturn(true);
        $purchaseProcess->method('cascade')->willReturn(Cascade::create(BillerCollection::buildBillerCollection([new EpochBiller()])));
        $purchaseProcess->method('state')->willReturn(Processed::create());

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->expects($this->once())->method('load')->willReturn($purchaseProcess);

        $transactionInformation = $this->createMock(TransactionInformation::class);
        $transactionInformation->method('status')->willReturn(Transaction::STATUS_PENDING);
        $transactionInformation->method('transactionId')->willReturn($this->faker->uuid);

        $retrievedTransactionResult = $this->createMock(EpochCCRetrieveTransactionResult::class);
        $retrievedTransactionResult->method('transactionInformation')->willReturn($transactionInformation);

        $billerInteraction = $this->createMock(EpochBillerInteraction::class);
        $billerInteraction->method('transactionId')->willReturn($this->faker->uuid);
        $billerInteraction->method('paymentType')->willReturn(CCPaymentInfo::PAYMENT_TYPE);
        $billerInteraction->method('status')->willReturn(Transaction::STATUS_APPROVED);

        $transactionService = $this->createMock(TransactionService::class);
        $transactionService->method('getTransactionDataBy')->willReturn($retrievedTransactionResult);
        $transactionService->method('addBillerInteraction')->willReturn($billerInteraction);

        $ConfigService = $this->createMock(ConfigService::class);
        $site          = $this->createMock(Site::class);
        $site->method('postbackUrl')->willReturn('postback-url');
        $ConfigService->method('getSite')->willReturn($site);

        $handler = new ThirdPartyPostbackCommandHandler(
            new ThirdPartyPostbackCommandDTOAssembler(),
            $processHandler,
            $transactionService,
            $this->createMock(PurchaseService::class),
            $this->createMock(UserInfoService::class),
            $this->createMock(BILoggerService::class),
            $ConfigService,
            $this->createMock(PostbackService::class),
            $this->createMock(TokenGenerator::class),
            $this->createMock(CryptService::class),
            $this->createMock(EventIngestionService::class)
        );

        /** @var ThirdPartyPostbackHttpDTO $result */
        $result = $handler->execute($this->command);

        $this->assertInstanceOf(ThirdPartyPostbackHttpDTO::class, $result);

        return $result->toArray();
    }

    /**
     * @test
     * @depends it_should_return_a_dto_when_a_valid_command_is_provided
     * @param array $dto DTO
     * @return void
     */
    public function it_should_return_a_dto_with_session_id_when_a_valid_command_is_provided(array $dto): void
    {
        $this->assertArrayHasKey('sessionId', $dto);
    }

    /**
     * @test
     * @depends it_should_return_a_dto_when_a_valid_command_is_provided
     * @param array $dto DTO
     * @return void
     */
    public function it_should_return_a_dto_with_result_key_when_a_valid_command_is_provided(array $dto): void
    {
        $this->assertArrayHasKey('result', $dto);
    }

    /**
     * @test
     * @return array
     * @throws \Exception
     */
    public function it_should_throw_exception_when_transaction_is_not_found(): array
    {
        $this->expectException(TransactionNotFoundException::class);

        $transaction = $this->createMock(Transaction::class);
        $transaction->method('transactionId')->willReturn(TransactionId::create());
        $transaction->method('state')->willReturn(Transaction::STATUS_PENDING);
        $transaction->method('billerName')->willReturn(EpochBiller::BILLER_NAME);

        $collection = new TransactionCollection();

        $chargeInformation = BundleSingleChargeInformation::create(Amount::create(1), Duration::create(30));

        $initItem = $this->createMock(InitializedItem::class);
        $initItem->method('transactionCollection')->willReturn($collection);
        $initItem->method('wasItemPurchasePending')->willReturn(true);
        $initItem->method('lastTransaction')->willReturn($transaction);
        $initItem->method('chargeInformation')->willReturn($chargeInformation);

        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initItem);
        $purchaseProcess->method('checkIfTransactionIdExist')->willReturn(false);
        $purchaseProcess->method('sessionId')->willReturn(SessionId::create());
        $purchaseProcess->method('isProcessed')->willReturn(true);
        $purchaseProcess->method('cascade')->willReturn(Cascade::create(BillerCollection::buildBillerCollection([new EpochBiller()])));
        $purchaseProcess->method('state')->willReturn(Processed::create());

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->expects($this->once())->method('load')->willReturn($purchaseProcess);

        $transactionInformation = $this->createMock(TransactionInformation::class);
        $transactionInformation->method('status')->willReturn(Transaction::STATUS_PENDING);
        $transactionInformation->method('transactionId')->willReturn($this->faker->uuid);

        $retrievedTransactionResult = $this->createMock(EpochCCRetrieveTransactionResult::class);
        $retrievedTransactionResult->method('transactionInformation')->willReturn($transactionInformation);

        $billerInteraction = $this->createMock(EpochBillerInteraction::class);
        $billerInteraction->method('transactionId')->willReturn($this->faker->uuid);
        $billerInteraction->method('paymentType')->willReturn(CCPaymentInfo::PAYMENT_TYPE);
        $billerInteraction->method('status')->willReturn(Transaction::STATUS_APPROVED);

        $transactionService = $this->createMock(TransactionService::class);
        $transactionService->method('getTransactionDataBy')->willReturn($retrievedTransactionResult);
        $transactionService->method('addBillerInteraction')->willReturn($billerInteraction);

        $siteRepository = $this->createMock(ConfigService::class);
        $site           = $this->createMock(Site::class);
        $site->method('postbackUrl')->willReturn('postback-url');
        $siteRepository->method('getSite')->willReturn($site);

        $handler = new ThirdPartyPostbackCommandHandler(
            new ThirdPartyPostbackCommandDTOAssembler(),
            $processHandler,
            $transactionService,
            $this->createMock(PurchaseService::class),
            $this->createMock(UserInfoService::class),
            $this->createMock(BILoggerService::class),
            $siteRepository,
            $this->createMock(PostbackService::class),
            $this->createMock(TokenGenerator::class),
            $this->createMock(CryptService::class),
            $this->createMock(EventIngestionService::class)
        );

        /** @var ThirdPartyPostbackHttpDTO $result */
        $handler->execute($this->command);
    }

    /**
     * @test
     * @return array
     * @throws \Exception
     */
    public function it_should_throw_transaction_already_process_exception_when_transaction_service_return_transaction_already_processed_in_postback(): array
    {
        $this->expectException(TransactionAlreadyProcessedException::class);

        $transaction = $this->createMock(Transaction::class);
        $transaction->method('transactionId')->willReturn(TransactionId::create());
        $transaction->method('state')->willReturn(Transaction::STATUS_PENDING);
        $transaction->method('billerName')->willReturn(EpochBiller::BILLER_NAME);

        $collection = new TransactionCollection();

        $chargeInformation = BundleSingleChargeInformation::create(Amount::create(1), Duration::create(30));

        $initItem = $this->createMock(InitializedItem::class);
        $initItem->method('transactionCollection')->willReturn($collection);
        $initItem->method('wasItemPurchasePending')->willReturn(true);
        $initItem->method('lastTransaction')->willReturn($transaction);
        $initItem->method('chargeInformation')->willReturn($chargeInformation);

        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initItem);
        $purchaseProcess->method('checkIfTransactionIdExist')->willReturn(true);
        $purchaseProcess->method('sessionId')->willReturn(SessionId::create());
        $purchaseProcess->method('isProcessed')->willReturn(true);
        $purchaseProcess->method('cascade')->willReturn(Cascade::create(BillerCollection::buildBillerCollection([new EpochBiller()])));
        $purchaseProcess->method('state')->willReturn(Processed::create());

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->expects($this->once())->method('load')->willReturn($purchaseProcess);

        $transactionInformation = $this->createMock(TransactionInformation::class);
        $transactionInformation->method('status')->willReturn(Transaction::STATUS_PENDING);
        $transactionInformation->method('transactionId')->willReturn($this->faker->uuid);

        $retrievedTransactionResult = $this->createMock(EpochCCRetrieveTransactionResult::class);
        $retrievedTransactionResult->method('transactionInformation')->willReturn($transactionInformation);

        $billerInteraction = $this->createMock(EpochBillerInteraction::class);
        $billerInteraction->method('transactionId')->willReturn($this->faker->uuid);
        $billerInteraction->method('paymentType')->willReturn(CCPaymentInfo::PAYMENT_TYPE);
        $billerInteraction->method('status')->willReturn(Transaction::STATUS_APPROVED);

        $transactionService = $this->createMock(TransactionService::class);
        $transactionService->method('getTransactionDataBy')->willReturn($retrievedTransactionResult);
        $transactionService->method('addBillerInteraction')
            ->willThrowException(
                new \ProBillerNG\PurchaseGateway\Domain\Services\Exception\TransactionAlreadyProcessedException()
            );

        $configService = $this->createMock(ConfigService::class);
        $site           = $this->createMock(Site::class);
        $site->method('postbackUrl')->willReturn('postback-url');
        $configService->method('getSite')->willReturn($site);

        $handler = new ThirdPartyPostbackCommandHandler(
            new ThirdPartyPostbackCommandDTOAssembler(),
            $processHandler,
            $transactionService,
            $this->createMock(PurchaseService::class),
            $this->createMock(UserInfoService::class),
            $this->createMock(BILoggerService::class),
            $configService,
            $this->createMock(PostbackService::class),
            $this->createMock(TokenGenerator::class),
            $this->createMock(CryptService::class),
            $this->createMock(EventIngestionService::class)
        );

        /** @var ThirdPartyPostbackHttpDTO $result */
        $handler->execute($this->command);
    }
}
