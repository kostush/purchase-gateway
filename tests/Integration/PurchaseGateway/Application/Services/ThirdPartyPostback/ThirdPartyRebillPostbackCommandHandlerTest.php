<?php

namespace Tests\Integration\PurchaseGateway\Application\Services\ThirdPartyPostback;

use Exception;
use ProBillerNG\BI\BILoggerService;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\PurchaseGateway\Application\DTO\ThirdPartyPostback\ThirdPartyPostbackCommandDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\ThirdPartyPostback\ThirdPartyPostbackHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyPostback\ThirdPartyPostbackCommand;
use ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyPostback\ThirdPartyPostbackCommandHandlerFactory;
use ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyPostback\ThirdPartyRebillPostbackCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\Amount;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleSingleChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;
use ProBillerNG\PurchaseGateway\Domain\Model\Duration;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidStateException;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItemCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Pending;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processed;
use ProBillerNG\PurchaseGateway\Domain\Model\QyssoBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\PostbackService;
use ProBillerNG\PurchaseGateway\Domain\Services\PurchaseService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\ThirdPartyRebillTransaction;
use Tests\IntegrationTestCase;

class ThirdPartyRebillPostbackCommandHandlerTest extends IntegrationTestCase
{
    /**
     * @var ThirdPartyPostbackCommand
     */
    private $command;

    /**
     * @return void
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->command = new ThirdPartyPostbackCommand(
            $this->faker->uuid,
            ['trans_order' => $this->faker->uuid],
            ThirdPartyPostbackCommandHandlerFactory::REBILL
        );
    }

    /**
     * @test
     * @return array
     * @throws Exception
     */
    public function it_should_return_a_dto_when_a_valid_command_is_provided(): array
    {
        $transaction = $this->createMock(Transaction::class);
        $transaction->method('transactionId')->willReturn(TransactionId::create());

        $collection = new TransactionCollection();
        $collection->add($transaction);

        $chargeInformation = BundleSingleChargeInformation::create(Amount::create(1), Duration::create(30));

        $initItem = $this->createMock(InitializedItem::class);
        $initItem->method('transactionCollection')->willReturn($collection);
        $initItem->method('lastTransactionState')->willReturn(Transaction::STATUS_APPROVED);
        $initItem->method('chargeInformation')->willReturn($chargeInformation);
        $initItem->method('lastTransactionId')->willReturn($transaction->transactionId());

        $initializedItemCollection = new InitializedItemCollection();
        $initializedItemCollection->add($initItem);


        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initItem);
        $purchaseProcess->method('cascade')->willReturn(
            Cascade::create(BillerCollection::buildBillerCollection([new QyssoBiller()]))
        );
        $purchaseProcess->method('state')->willReturn(Processed::create());
        $purchaseProcess->method('initializedItemCollection')->willReturn($initializedItemCollection);

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->expects($this->once())->method('load')->willReturn($purchaseProcess);

        $thirdPartyRebillTransaction = $this->createMock(ThirdPartyRebillTransaction::class);
        $thirdPartyRebillTransaction->method('transactionId')->willReturn(TransactionId::create());
        $thirdPartyRebillTransaction->method('state')->willReturn('approved');

        $transactionService = $this->createMock(TransactionService::class);

        $transactionService->method('createRebillTransaction')->willReturn($thirdPartyRebillTransaction);

        $configService = $this->createMock(ConfigService::class);
        $site          = $this->createMock(Site::class);
        $site->method('postbackUrl')->willReturn('postback-url');
        $configService->method('getSite')->willReturn($site);

        $handler = new ThirdPartyRebillPostbackCommandHandler(
            new ThirdPartyPostbackCommandDTOAssembler(),
            $processHandler,
            $transactionService,
            $this->createMock(PurchaseService::class),
            $this->createMock(BILoggerService::class),
            $configService,
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
     * @throws Exception
     */
    public function it_should_throw_invalid_state_exception_if_previous_session_was_not_processed(): array
    {
        $this->expectException(InvalidStateException::class);

        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $purchaseProcess->method('state')->willReturn(Pending::create());

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->method('load')->willReturn($purchaseProcess);

        $handler = new ThirdPartyRebillPostbackCommandHandler(
            new ThirdPartyPostbackCommandDTOAssembler(),
            $processHandler,
            $this->createMock(TransactionService::class),
            $this->createMock(PurchaseService::class),
            $this->createMock(BILoggerService::class),
            $this->createMock(ConfigService::class),
            $this->createMock(PostbackService::class),
            $this->createMock(TokenGenerator::class),
            $this->createMock(CryptService::class),
            $this->createMock(EventIngestionService::class)
        );

        $handler->execute($this->command);
    }
}
