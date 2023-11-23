<?php

declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Domain\Services;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Application\Exceptions\UnknownBillerNameException;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\BinRouting;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\NewCCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionTranslatingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\EpochBillerInteraction;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\TransactionInformation;
use Tests\IntegrationTestCase;
use Throwable;

class TransactionServiceTest extends IntegrationTestCase
{
    /**
     * @var TransactionTranslatingService
     */
    private $transactionTranslatingService;

    /**
     * @var TokenGenerator
     */
    private $tokenGenerator;

    /**
     * @var CryptService
     */
    protected $cryptService;

    /**
     * @var Transaction
     */
    private $transaction;

    /**
     * @var InitializedItem
     */
    private $initializedItem;

    /**
     * @var TransactionInformation
     */
    private $transactionInformation;

    /**
     * @return void
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub

        $this->transaction = $this->createMock(Transaction::class);
        $this->transaction->method('transactionId')->willReturn(TransactionId::create());
        $this->transaction->method('billerName')->willReturn('rocketgate');
        $this->transaction->method('successfulBinRouting')->willReturn($this->createMock(BinRouting::class));

        $this->initializedItem = $this->createMock(InitializedItem::class);
        $this->initializedItem->method('lastTransactionId')->willReturn(TransactionId::create());
        $this->initializedItem->method('wasItemPurchaseSuccessful')->willReturn(true);
        $this->initializedItem->method('transactionCollection')->willReturn(new TransactionCollection([$this->transaction]));

        $this->transactionInformation = $this->createRocketgateCCRetrieveTransactionResultMocks(
            $this->faker->uuid,
            $this->faker->uuid,
            $this->faker->uuid,
            [],
            true
        );

        $this->transactionTranslatingService = $this->createMock(TransactionTranslatingService::class);
        $this->cryptService                  = $this->createMock(CryptService::class);
        $this->tokenGenerator                = $this->createMock(TokenGenerator::class);
    }

    /**
     * @test
     * @return void
     * @throws Throwable
     * @throws Exception
     */
    public function attempt_transactions_should_call_perform_complete_3d_and_perform_existing_card_transactions(): void
    {
        $this->transactionTranslatingService->method('getTransactionDataBy')
            ->willReturn($this->transactionInformation);
        $this->transactionTranslatingService->expects($this->once())
            ->method('performCompleteThreeDTransaction')
            ->willReturn($this->transaction);
        $this->transactionTranslatingService->expects($this->once())
            ->method('performTransactionWithExistingCard')
            ->willReturn($this->transaction);

        $transaction = Transaction::create(
            TransactionId::createFromString($this->faker->uuid),
            Transaction::STATUS_PENDING,
            RocketgateBiller::BILLER_NAME
        );
        $transaction->setThreeDVersion(1);
        $this->initializedItem->method('lastTransaction')
            ->willReturn($transaction);

        $transactionService = new TransactionService(
            $this->transactionTranslatingService,
            $this->tokenGenerator,
            $this->cryptService
        );

        $result = $transactionService->attemptCompleteThreeDTransaction(
            $this->initializedItem,
            [$this->initializedItem],
            $this->createSite(),
            $this->createMock(FraudAdvice::class),
            $this->createMock(UserInfo::class),
            SessionId::create(),
            'pares',
            null,
            'visa'
        );

        $this->assertInstanceOf(TransactionInformation::class, $result);
    }

    /**
     * @test
     * @return void
     * @throws Throwable
     */
    public function attempt_transactions_should_return_transaction_information_and_continue_the_purchase_flow_when_exception_is_thrown_during_cross_sale_transaction_attempt(): void
    {
        $this->transactionTranslatingService->method('getTransactionDataBy')
            ->willReturn($this->transactionInformation);
        $this->transactionTranslatingService->expects($this->once())
            ->method('performCompleteThreeDTransaction')
            ->willReturn($this->transaction);


        $transaction = Transaction::create(
            TransactionId::createFromString($this->faker->uuid),
            Transaction::STATUS_PENDING,
            RocketgateBiller::BILLER_NAME
        );
        $transaction->setThreeDVersion(1);
        $this->initializedItem->method('lastTransaction')
            ->willReturn($transaction);

        $transactionService = $this->getMockBuilder(TransactionService::class)
            ->setConstructorArgs(
                [
                    $this->transactionTranslatingService,
                    $this->tokenGenerator,
                    $this->cryptService
                ]
            )
            ->onlyMethods(['attemptCrossSaleTransactions'])
            ->getMock();
        $transactionService->method('attemptCrossSaleTransactions')->willThrowException(new \Exception('error'));

        $result = $transactionService->attemptCompleteThreeDTransaction(
            $this->initializedItem,
            [$this->initializedItem],
            $this->createSite(),
            $this->createMock(FraudAdvice::class),
            $this->createMock(UserInfo::class),
            SessionId::create(),
            'pares',
            null,
            null
        );

        $this->assertInstanceOf(TransactionInformation::class, $result);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws \Exception
     */
    public function add_biller_interaction_should_return_epoch_biller_interaction(): void
    {
        $this->transactionTranslatingService->expects($this->once())
            ->method('addEpochBillerInteraction')
            ->willReturn($this->createMock(EpochBillerInteraction::class));

        $transactionService = new TransactionService(
            $this->transactionTranslatingService,
            $this->tokenGenerator,
            $this->cryptService
        );

        $result = $transactionService->addBillerInteraction(
            TransactionId::create(),
            EpochBiller::BILLER_NAME,
            SessionId::create(),
            []
        );

        $this->assertInstanceOf(EpochBillerInteraction::class, $result);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws \Exception
     */
    public function add_biller_interaction_should_throw_exception_when_transaction_is_not_epoch(): void
    {
        $this->expectException(UnknownBillerNameException::class);

        $transactionService = new TransactionService(
            $this->transactionTranslatingService,
            $this->tokenGenerator,
            $this->cryptService
        );
        $transactionService->addBillerInteraction(
            TransactionId::create(),
            'randomName',
            SessionId::create(),
            []
        );
    }

    /**
     * @test
     * @return void
     * @throws Throwable
     * @throws Exception
     */
    public function lookup_transactions_should_call_perform_lookup_and_perform_existing_card_transactions(): void
    {
        $this->transactionTranslatingService->method('getTransactionDataBy')
            ->willReturn($this->transactionInformation);
        $this->transactionTranslatingService->expects($this->once())
            ->method('performLookupTransaction')
            ->willReturn($this->transaction);
        $this->transactionTranslatingService->expects($this->once())
            ->method('performTransactionWithExistingCard')
            ->willReturn($this->transaction);

        $transactionService = new TransactionService(
            $this->transactionTranslatingService,
            $this->tokenGenerator,
            $this->cryptService
        );

        $this->initializedItem->method('lastTransaction')->willReturn($this->transaction);
        $result = $transactionService->lookupTransaction(
            $this->initializedItem,
            NewCCPaymentInfo::create(
                $this->faker->creditCardNumber,
                '123',
                $this->faker->month,
                '2030',
                'visa'
            ),
            [$this->initializedItem],
            $this->createSite(),
            $this->createMock(FraudAdvice::class),
            $this->createMock(UserInfo::class),
            $this->faker->url,
            'devicefingerPrintId',
            false
        );

        $this->assertInstanceOf(Transaction::class, $result);
    }
}
