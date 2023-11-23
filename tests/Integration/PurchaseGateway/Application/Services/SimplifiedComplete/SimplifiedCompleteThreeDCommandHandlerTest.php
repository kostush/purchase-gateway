<?php

namespace Tests\Integration\PurchaseGateway\Application\Services\SimplifiedComplete;

use Exception;
use ProBillerNG\BI\BILoggerService;
use ProBillerNG\Crypt\Sodium\InvalidPrivateKeySizeException;
use ProBillerNG\Crypt\Sodium\PrivateKeyConfig;
use ProBillerNG\Crypt\Sodium\PrivateKeyCypher;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\PurchaseGateway\Application\DTO\Complete\CompleteThreeDCommandDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\Complete\CompleteThreeDHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Exceptions\MissingMandatoryQueryParamsForCompleteException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\MissingRedirectUrlException;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Application\Services\SimplifiedComplete\SimplifiedCompleteThreeDCommand;
use ProBillerNG\PurchaseGateway\Application\Services\SimplifiedComplete\SimplifiedCompleteThreeDCommandHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\InMemoryRepository;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Service;
use ProBillerNG\PurchaseGateway\Domain\Model\ServiceCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\ServicesList;
use ProBillerNG\PurchaseGateway\Domain\Services\PostbackService;
use ProBillerNG\PurchaseGateway\Domain\Services\PurchaseService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\JsonWebTokenGenerator;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\SodiumCryptService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\ExistingCCTransactionInformation;
use Tests\IntegrationTestCase;
use Throwable;

class SimplifiedCompleteThreeDCommandHandlerTest extends IntegrationTestCase
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
     * @var SimplifiedCompleteThreeDCommand
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
     * @var \PHPUnit\Framework\MockObject\MockObject|InMemoryRepository
     */
    private $redisRepository;

    /**
     * @return void
     * @throws InvalidPrivateKeySizeException
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->purchaseProcess     = $this->createMock(PurchaseProcess::class);
        $this->processHandler      = $this->createMock(PurchaseProcessHandler::class);
        $this->biServiceMock       = $this->createMock(BILoggerService::class);
        $this->postbackMock        = $this->createMock(PostbackService::class);
        $this->configServiceClient = $this->createMock(ConfigService::class);
        $this->purchaseService     = $this->createMock(PurchaseService::class);
        $this->transactionService  = $this->createMock(TransactionService::class);
        $this->tokenGenerator      = new JsonWebTokenGenerator();
        $this->cryptService        = new SodiumCryptService(
            new PrivateKeyCypher(
                new PrivateKeyConfig(
                    env('APP_CRYPT_KEY')
                )
            )
        );

        $serviceCollection = new ServiceCollection();
        $serviceCollection->add(Service::create(ServicesList::FRAUD, true));

        $this->configServiceClient->method('getSite')->willReturn(
            $this->createSite(
                false,
                false,
                $serviceCollection
            )
        );

        $transactionInfo = $this->createMock(ExistingCCTransactionInformation::class);
        $transactionInfo->method('cardHash')->willReturn('f4E7crlzpJKmKuXJyS50gBxHDLnCX9VIbEbM0q4F2qg=');
        $transactionInfo->method('first6')->willReturn('123456');
        $transactionInfo->method('last4')->willReturn('1234');
        $transactionInfo->method('cardExpirationMonth')->willReturn(11);
        $transactionInfo->method('cardExpirationYear')->willReturn(2222);
        $this->transactionService->method('attemptSimplifiedCompleteThreeDTransaction')
            ->willReturn($transactionInfo);

        parse_str(
            'flag=17c6f59e222&id=64d98d86-61642f822233e7.53329385&invoiceID=aba9b991-61642f82223498.08058272&hash=4qEW12Qdl5%2FYxkCtRbZ%2FHT%2Bi1NM%3D',
            $queryString
        );

        $this->command = new SimplifiedCompleteThreeDCommand(
            $this->faker->uuid,
            $queryString
        );

        $this->redisRepository = $this->createMock(InMemoryRepository::class);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws Throwable
     */
    public function it_should_successfully_execute_command_and_return_the_simplified_complete_response(): void
    {
        $sessionPayload = json_decode($this->latestVersionSessionPayload(), true);

        $mainItem                                    = $sessionPayload['initializedItemCollection'][0];
        $mainItem['subscriptionId']                  = $this->faker->uuid;
        $mainItem['addonId']                         = $this->faker->uuid;
        $mainItem['bundleId']                        = $this->faker->uuid;
        $mainItem['isCrossSale']                     = false;
        $sessionPayload['redirectUrl']               = $this->faker->url;
        $sessionPayload['memberId']                  = $this->faker->uuid;
        $sessionPayload['state']                     = 'pending';
        $sessionPayload['initializedItemCollection'] = [
            $mainItem
        ];

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->method('load')->willReturn(
            PurchaseProcess::restore($sessionPayload)
        );

        $handler = new SimplifiedCompleteThreeDCommandHandler(
            new CompleteThreeDCommandDTOAssembler($this->tokenGenerator, $this->cryptService),
            $this->transactionService,
            $processHandler,
            $this->configServiceClient,
            $this->purchaseService,
            $this->postbackMock,
            $this->biServiceMock,
            $this->createMock(EventIngestionService::class),
            $this->redisRepository
        );

        /** @var SimplifiedCompleteThreeDCommandHandler $result */
        $result = $handler->execute($this->command);

        self::assertInstanceOf(CompleteThreeDHttpDTO::class, $result);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws Throwable
     */
    public function it_should_should_call_event_ingestion_system_n_times_according_feature_toggle(): void
    {
        $sessionPayload             = json_decode($this->latestVersionSessionPayload(), true);
        $mainItem                   = $sessionPayload['initializedItemCollection'][0];
        $mainItem['subscriptionId'] = $this->faker->uuid;
        $mainItem['addonId']        = $this->faker->uuid;
        $mainItem['bundleId']       = $this->faker->uuid;
        $mainItem['isCrossSale']    = false;

        $sessionPayload['initializedItemCollection'] = [$mainItem];
        $sessionPayload['redirectUrl']               = $this->faker->url;
        $sessionPayload['memberId']                  = $this->faker->uuid;
        $sessionPayload['state']                     = 'pending';

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->method('load')->willReturn(PurchaseProcess::restore($sessionPayload));

        $callsToEventIngestionSystem = 0;

        /* @var $eventIngestionSystem EventIngestionService */
        $eventIngestionSystem = $this->getMockBuilder(EventIngestionService::class)
            ->onlyMethods(['queue'])
            ->disableOriginalConstructor()
            ->getMock();

        if (config('app.feature.event_ingestion_communication.send_3ds_fraud_event')) {
            $callsToEventIngestionSystem++;
        }

        if (config('app.feature.event_ingestion_communication.send_general_bi_events')) {
            $callsToEventIngestionSystem++;
        }

        if (config('app.feature.event_ingestion_communication.send_general_bi_events')) {
            $callsToEventIngestionSystem++;
        }

        if (config('app.feature.event_ingestion_communication.send_fraud_velocity_event')) {
            $callsToEventIngestionSystem += 2;
        }

        $eventIngestionSystem->expects($this->exactly($callsToEventIngestionSystem))->method('queue');

        $handler = new SimplifiedCompleteThreeDCommandHandler(
            new CompleteThreeDCommandDTOAssembler($this->tokenGenerator, $this->cryptService),
            $this->transactionService,
            $processHandler,
            $this->configServiceClient,
            $this->purchaseService,
            $this->postbackMock,
            $this->biServiceMock,
            $eventIngestionSystem,
            $this->redisRepository
        );

        /** @var SimplifiedCompleteThreeDCommandHandler $result */
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

        $sessionPayload = json_decode($this->latestVersionSessionPayload(), true);

        $mainItem                                    = $sessionPayload['initializedItemCollection'][0];
        $mainItem['subscriptionId']                  = $this->faker->uuid;
        $mainItem['addonId']                         = $this->faker->uuid;
        $mainItem['bundleId']                        = $this->faker->uuid;
        $mainItem['isCrossSale']                     = false;
        $sessionPayload['initializedItemCollection'] = [$mainItem];
        $sessionPayload['memberId']                  = $this->faker->uuid;
        $sessionPayload['state']                     = 'processed';

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->method('load')->willReturn(
            PurchaseProcess::restore($sessionPayload)
        );

        $handler = new SimplifiedCompleteThreeDCommandHandler(
            new CompleteThreeDCommandDTOAssembler($this->tokenGenerator, $this->cryptService),
            $this->transactionService,
            $processHandler,
            $this->configServiceClient,
            $this->purchaseService,
            $this->postbackMock,
            $this->biServiceMock,
            $this->createMock(EventIngestionService::class),
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
    public function it_should_throw_exception_when_missing_query_string(): void
    {
        $this->expectException(MissingMandatoryQueryParamsForCompleteException::class);

        $sessionPayload = json_decode($this->latestVersionSessionPayload(), true);

        $mainItem                                    = $sessionPayload['initializedItemCollection'][0];
        $mainItem['subscriptionId']                  = $this->faker->uuid;
        $mainItem['addonId']                         = $this->faker->uuid;
        $mainItem['bundleId']                        = $this->faker->uuid;
        $mainItem['isCrossSale']                     = false;
        $sessionPayload['initializedItemCollection'] = [$mainItem];
        $sessionPayload['redirectUrl']               = $this->faker->url;

        $sessionPayload['memberId'] = $this->faker->uuid;
        $sessionPayload['state']    = 'pending';

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->method('load')->willReturn(
            PurchaseProcess::restore($sessionPayload)
        );

        $handler = new SimplifiedCompleteThreeDCommandHandler(
            new CompleteThreeDCommandDTOAssembler($this->tokenGenerator, $this->cryptService),
            $this->transactionService,
            $processHandler,
            $this->configServiceClient,
            $this->purchaseService,
            $this->postbackMock,
            $this->biServiceMock,
            $this->createMock(EventIngestionService::class),
            $this->redisRepository
        );

        $command = new SimplifiedCompleteThreeDCommand(
            $this->faker->uuid,
            []
        );

        $handler->execute($command);
    }
}
