<?php

namespace Tests\Integration\PurchaseGateway\Application\Services\ThirdPartyReturn;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\BI\BILoggerService;
use ProBillerNG\Crypt\Sodium\PrivateKeyConfig;
use ProBillerNG\Crypt\Sodium\PrivateKeyCypher;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\PurchaseGateway\Application\DTO\ThirdPartyReturn\ReturnDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\ThirdPartyReturn\ReturnHttpCommandDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\ThirdPartyReturn\ReturnHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidPayloadException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\MissingRedirectUrlException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionAlreadyProcessedException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\TransactionAlreadyProcessedException;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyReturn\ReturnCommand;
use ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyReturn\ReturnCommandHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\MemberId;
use ProBillerNG\PurchaseGateway\Domain\Model\Purchase;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseId;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Services\PostbackService;
use ProBillerNG\PurchaseGateway\Domain\Services\PurchaseService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Domain\Services\UserInfoService;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\JsonWebTokenGenerator;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\SodiumCryptService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\NewCCTransactionInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\EpochBillerInteraction;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\EpochCCRetrieveTransactionResult;
use SessionHandler;
use Tests\IntegrationTestCase;

class ReturnCommandHandlerTest extends IntegrationTestCase
{
    /**
     * @var PurchaseProcess
     */
    protected $purchaseProcess;

    /**
     * @var ConfigService|MockObject
     */
    protected $configService;

    /**
     * @var BILoggerService
     */
    private $biLoggerService;

    /**
     * @var ReturnDTOAssembler
     */
    protected $assembler;

    /**
     * @var SessionHandler|MockObject
     */
    protected $purchaseProcessHandler;

    /**
     * @var TransactionService|MockObject
     */
    protected $transactionService;

    /**
     * @var PurchaseService|MockObject
     */
    protected $purchaseService;

    /**
     * @var UserInfoService
     */
    protected $userInfoService;

    /**
     * @var Purchase
     */
    protected $purchase;

    /**
     * @var PostbackService
     */
    protected $postbackService;

    /**
     * @var ReturnCommandHandler
     */
    protected $handler;

    /**
     * @var EventIngestionService
     */
    private $eventIngestion;


    /**
     * @return void
     * @throws \ProBillerNG\Crypt\Sodium\InvalidPrivateKeySizeException
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->configService         = $this->createMock(ConfigService::class);
        $this->transactionService     = $this->createMock(TransactionService::class);
        $this->purchaseProcessHandler = $this->createMock(PurchaseProcessHandler::class);
        $this->purchaseService        = $this->createMock(PurchaseService::class);
        $this->postbackService        = $this->createMock(PostbackService::class);
        $this->biLoggerService        = $this->createMock(BILoggerService::class);
        $this->eventIngestion         = $this->createMock(EventIngestionService::class);
        $this->userInfoService        = new UserInfoService();

        $processedItemsCollection = $this->initProcessedItems();
        $this->purchase           = Purchase::create(
            PurchaseId::create(),
            MemberId::create(),
            SessionId::create(),
            $processedItemsCollection,
            Transaction::STATUS_APPROVED
        );

        $this->assembler = new ReturnHttpCommandDTOAssembler(
            new JsonWebTokenGenerator(),
            new SodiumCryptService(
                new PrivateKeyCypher(
                    new PrivateKeyConfig(
                        env('APP_CRYPT_KEY')
                    )
                )
            )
        );

        $this->handler = $this->getMockBuilder(ReturnCommandHandler::class)
            ->setConstructorArgs(
                [
                    $this->assembler,
                    $this->purchaseProcessHandler,
                    $this->transactionService,
                    $this->purchaseService,
                    $this->configService,
                    $this->postbackService,
                    $this->biLoggerService,
                    $this->userInfoService,
                    $this->eventIngestion
                ]
            )
            ->onlyMethods(
                [
                    'shipBiProcessedPurchaseEvent'
                ]
            )
            ->getMock();
    }

    /**
     * @test
     * @return array
     * @throws \Exception
     */
    public function it_should_return_a_dto_when_a_valid_command_is_provided(): array
    {
        $sessionPayload = json_decode($this->latestVersionSessionPayload(), true);

        $mainItem                   = $sessionPayload['initializedItemCollection'][0];
        $mainItem['subscriptionId'] = $this->faker->uuid;
        $mainItem['addonId']        = $this->faker->uuid;
        $mainItem['bundleId']       = $this->faker->uuid;
        $mainItem['isCrossSale']    = false;

        $sessionPayload['initializedItemCollection'] = [$mainItem];
        $sessionPayload['redirectUrl']               = $this->faker->url;
        $sessionPayload['state']                     = 'redirected';

        $this->purchaseProcessHandler->method('load')->willReturn(PurchaseProcess::restore($sessionPayload));

        $transactionId = $this->faker->uuid;
        $transaction   = $this->createMock(EpochCCRetrieveTransactionResult::class);
        $this->transactionService->method('getTransactionDataBy')->willReturn($transaction);

        $transactionInformation = $this->createMock(NewCCTransactionInformation::class);
        $transaction->method('transactionInformation')->willReturn($transactionInformation);
        $transactionInformation->method('status')->willReturn(Transaction::STATUS_PENDING);
        $transactionInformation->method('transactionId')->willReturn($transactionId);

        $transaction->method('transactionInformation')->willReturn(EpochCCRetrieveTransactionResult::class);

        $billerInteraction = $this->createMock(EpochBillerInteraction::class);
        $billerInteraction->method('status')->willReturn('approved');
        $billerInteraction->method('paymentType')->willReturn('cc');
        $billerInteraction->method('paymentMethod')->willReturn('visa');
        $this->transactionService->method('addBillerInteraction')->willReturn($billerInteraction);

        $this->configService->method('getSite')->willReturn($this->createSite());

        $command = new ReturnCommand(
            [
                'ngTransactionId' => $transactionId,
                'name'            => 'John Snow',
                'email'           => 'winter.is.coming@summer.com',
                'zip'             => 'AAABBB',
            ],
            $this->faker->uuid
        );

        /** @var ReturnCommandHandler $handler */
        $handler = $this->getMockBuilder(ReturnCommandHandler::class)
            ->setConstructorArgs(
                [
                    $this->assembler,
                    $this->purchaseProcessHandler,
                    $this->transactionService,
                    $this->purchaseService,
                    $this->configService,
                    $this->postbackService,
                    $this->biLoggerService,
                    $this->userInfoService,
                    $this->eventIngestion
                ]
            )
            ->onlyMethods(
                [
                    'shipBiProcessedPurchaseEvent',
                    'validatePurchaseProcess'
                ]
            )
            ->getMock();

        /** @var ReturnHttpDTO $result */
        $result = $handler->execute($command);

        $this->assertInstanceOf(ReturnHttpDTO::class, $result);

        return $result->jsonSerialize();
    }

    /**
     * @test
     * @depends it_should_return_a_dto_when_a_valid_command_is_provided
     * @param array $result Result
     * @return void
     */
    public function it_should_return_a_dto_with_a_success_key_when_a_valid_command_is_provided(array $result): void
    {
        $this->assertArrayHasKey('success', $result);
    }

    /**
     * @test
     * @depends it_should_return_a_dto_when_a_valid_command_is_provided
     * @param array $result Result
     * @return void
     */
    public function it_should_return_a_dto_with_a_purchase_id_key_when_a_valid_command_is_provided(array $result): void
    {
        $this->assertArrayHasKey('purchaseId', $result);
    }

    /**
     * @test
     * @depends it_should_return_a_dto_when_a_valid_command_is_provided
     * @param array $result Result
     * @return void
     */
    public function it_should_return_a_dto_with_a_member_id_key_when_a_valid_command_is_provided(array $result): void
    {
        $this->assertArrayHasKey('memberId', $result);
    }

    /**
     * @test
     * @depends it_should_return_a_dto_when_a_valid_command_is_provided
     * @param array $result Result
     * @return void
     */
    public function it_should_return_a_dto_with_a_next_action_key_when_a_valid_command_is_provided(array $result): void
    {
        $this->assertArrayHasKey('nextAction', $result);
    }

    /**
     * @test
     * @depends it_should_return_a_dto_when_a_valid_command_is_provided
     * @param array $result Result
     * @return void
     */
    public function it_should_return_a_dto_with_a_bundle_id_key_when_a_valid_command_is_provided(array $result): void
    {
        $this->assertArrayHasKey('bundleId', $result);
    }

    /**
     * @test
     * @depends it_should_return_a_dto_when_a_valid_command_is_provided
     * @param array $result Result
     * @return void
     */
    public function it_should_return_a_dto_with_a_addon_id_key_when_a_valid_command_is_provided(array $result): void
    {
        $this->assertArrayHasKey('addonId', $result);
    }

    /**
     * @test
     * @depends it_should_return_a_dto_when_a_valid_command_is_provided
     * @param array $result Result
     * @return void
     */
    public function it_should_return_a_dto_with_a_item_id_key_when_a_valid_command_is_provided(array $result): void
    {
        $this->assertArrayHasKey('itemId', $result);
    }

    /**
     * @test
     * @depends it_should_return_a_dto_when_a_valid_command_is_provided
     * @param array $result Result
     * @return void
     */
    public function it_should_return_a_dto_with_a_digest_key_when_a_valid_command_is_provided(array $result): void
    {
        $this->assertArrayHasKey('digest', $result);
    }

    /**
     * @test
     * @depends it_should_return_a_dto_when_a_valid_command_is_provided
     * @param array $result Result
     * @return void
     */
    public function it_should_return_a_dto_with_a_redirect_url_key_when_a_valid_command_is_provided(array $result): void
    {
        $this->assertArrayHasKey('redirectUrl', $result);
    }

    /**
     * @test
     * @throws \Exception
     * @throws \Throwable
     * @return void
     */
    public function it_should_throw_exception_when_missing_redirect_url_in_the_session_object(): void
    {
        $this->expectException(MissingRedirectUrlException::class);

        $sessionPayload = json_decode($this->latestVersionSessionPayload(), true);

        $mainItem                   = $sessionPayload['initializedItemCollection'][0];
        $mainItem['subscriptionId'] = $this->faker->uuid;
        $mainItem['addonId']        = $this->faker->uuid;
        $mainItem['bundleId']       = $this->faker->uuid;
        $mainItem['isCrossSale']    = false;

        $sessionPayload['initializedItemCollection'] = [$mainItem];
        $sessionPayload['state']                     = 'redirected';

        $this->purchaseProcessHandler->method('load')->willReturn(PurchaseProcess::restore($sessionPayload));

        $command = new ReturnCommand(
            [
                'ngTransactionId' => $this->faker->uuid,
                'name'            => 'John Snow',
                'email'           => 'winter.is.coming@summer.com',
                'zip'             => 'AAABBB',
            ],
            $this->faker->uuid
        );

        /** @var ReturnHttpDTO $result */
        $this->handler->execute($command);
    }

    /**
     * @test
     * @throws \Exception
     * @throws \Throwable
     * @return void
     */
    public function it_should_throw_transaction_already_process_exception_when_transaction_service_return_transaction_already_processed_in_return(): void
    {
        $this->expectException(TransactionAlreadyProcessedException::class);

        $sessionPayload = json_decode($this->latestVersionSessionPayload(), true);

        $mainItem                   = $sessionPayload['initializedItemCollection'][0];
        $mainItem['subscriptionId'] = $this->faker->uuid;
        $mainItem['addonId']        = $this->faker->uuid;
        $mainItem['bundleId']       = $this->faker->uuid;
        $mainItem['isCrossSale']    = false;

        $sessionPayload['initializedItemCollection'] = [$mainItem];
        $sessionPayload['state']                     = 'redirected';
        $sessionPayload['redirectUrl']               = $this->faker->url;

        $transactionId = $sessionPayload['initializedItemCollection'][0]['transactionCollection'][0]['transactionId'];

        $this->purchaseProcess = $this->purchaseProcessHandler->method('load')
            ->willReturn(PurchaseProcess::restore($sessionPayload));

        $transaction            = $this->createMock(EpochCCRetrieveTransactionResult::class);
        $transactionInformation = $this->createMock(NewCCTransactionInformation::class);
        $transactionInformation->method('status')->willReturn(Transaction::STATUS_PENDING);
        $transactionInformation->method('transactionId')->willReturn($transactionId);

        $transaction->method('transactionInformation')->willReturn($transactionInformation);
        $transaction->method('transactionInformation')
            ->willReturn(EpochCCRetrieveTransactionResult::class);

        $this->transactionService->method('getTransactionDataBy')->willReturn($transaction);
        $this->transactionService->method('addBillerInteraction')
            ->willThrowException(
                new \ProBillerNG\PurchaseGateway\Domain\Services\Exception\TransactionAlreadyProcessedException()
            );

        $command = new ReturnCommand(
            [
                'ngTransactionId' => $transactionId,
                'name'            => 'John Snow',
                'email'           => 'winter.is.coming@summer.com',
                'zip'             => 'AAABBB',
            ],
            $this->faker->uuid
        );

        /** @var ReturnHttpDTO $result */
        $this->handler->execute($command);
    }

    /**
     * @test
     * @throws \Exception
     * @throws \Throwable
     * @return void
     */
    public function it_should_throw_exception_when_return_transaction_not_found(): void
    {
        $this->expectException(InvalidPayloadException::class);

        $sessionPayload = json_decode($this->latestVersionSessionPayload(), true);

        $mainItem                   = $sessionPayload['initializedItemCollection'][0];
        $mainItem['subscriptionId'] = $this->faker->uuid;
        $mainItem['addonId']        = $this->faker->uuid;
        $mainItem['bundleId']       = $this->faker->uuid;
        $mainItem['isCrossSale']    = false;

        $sessionPayload['initializedItemCollection'] = [$mainItem];
        $sessionPayload['redirectUrl']               = $this->faker->url;
        $sessionPayload['state']                     = 'redirected';

        $this->purchaseProcessHandler->method('load')->willReturn(PurchaseProcess::restore($sessionPayload));

        $command = new ReturnCommand(
            [
                'ngTransactionId' => $this->faker->uuid,
                'name'            => 'John Snow',
                'email'           => 'winter.is.coming@summer.com',
                'zip'             => 'AAABBB',
            ],
            $this->faker->uuid
        );

        /** @var ReturnHttpDTO $result */
        $this->handler->execute($command);
    }

    /**
     * @test
     * @throws \Exception
     * @throws \Throwable
     * @return void
     */
    public function it_should_throw_exception_when_purchase_process_state_is_not_redirected_or_processed(): void
    {
        $this->expectException(SessionAlreadyProcessedException::class);

        $sessionPayload = json_decode($this->latestVersionSessionPayload(), true);

        $mainItem                   = $sessionPayload['initializedItemCollection'][0];
        $mainItem['subscriptionId'] = $this->faker->uuid;
        $mainItem['addonId']        = $this->faker->uuid;
        $mainItem['bundleId']       = $this->faker->uuid;
        $mainItem['isCrossSale']    = false;

        $sessionPayload['initializedItemCollection'] = [$mainItem];
        $sessionPayload['redirectUrl']               = $this->faker->url;
        $sessionPayload['state']                     = 'pending';

        $this->purchaseProcessHandler->method('load')->willReturn(PurchaseProcess::restore($sessionPayload));

        $command = new ReturnCommand(
            [
                'ngTransactionId' => $this->faker->uuid,
                'name'            => 'John Snow',
                'email'           => 'winter.is.coming@summer.com',
                'zip'             => 'AAABBB',
            ],
            $this->faker->uuid
        );

        /** @var ReturnHttpDTO $result */
        $this->handler->execute($command);
    }
}
