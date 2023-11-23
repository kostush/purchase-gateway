<?php

namespace Tests\Integration\PurchaseGateway\Application\Services\Complete;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\BI\BILoggerService;
use ProBillerNG\Crypt\Sodium\InvalidPrivateKeySizeException;
use ProBillerNG\Crypt\Sodium\PrivateKeyConfig;
use ProBillerNG\Crypt\Sodium\PrivateKeyCypher;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\PurchaseGateway\Application\DTO\Complete\CompleteThreeDCommandDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\Complete\CompleteThreeDHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Exceptions\MissingParesAndMdException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\MissingRedirectUrlException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionAlreadyProcessedException;
use ProBillerNG\PurchaseGateway\Application\Services\Complete\CompleteThreeDCommand;
use ProBillerNG\PurchaseGateway\Application\Services\Complete\CompleteThreeDCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\BusinessGroupId;
use ProBillerNG\PurchaseGateway\Domain\Model\PublicKeyCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Service;
use ProBillerNG\PurchaseGateway\Domain\Model\ServiceCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\ServicesList;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Services\CCForBlackListService;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudService;
use ProBillerNG\PurchaseGateway\Domain\Services\PostbackService;
use ProBillerNG\PurchaseGateway\Domain\Services\PurchaseService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\JsonWebTokenGenerator;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\SodiumCryptService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\NewCCTransactionInformation;
use Tests\IntegrationTestCase;

class CompleteThreeDCommandHandlerTest extends IntegrationTestCase
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
     * @var MockObject|CCForBlackListService
     */
    private $ccForBlackListService;

    /**
     * @return void
     * @throws InvalidPrivateKeySizeException
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->purchaseProcess       = $this->createMock(PurchaseProcess::class);
        $this->processHandler        = $this->createMock(PurchaseProcessHandler::class);
        $this->tokenGenerator        = new JsonWebTokenGenerator();
        $this->biServiceMock         = $this->createMock(BILoggerService::class);
        $this->postbackMock          = $this->createMock(PostbackService::class);
        $this->ccForBlackListService = $this->createMock(CCForBlackListService::class);
        $this->cryptService          = new SodiumCryptService(
            new PrivateKeyCypher(
                new PrivateKeyConfig(
                    env('APP_CRYPT_KEY')
                )
            )
        );

        $this->configServiceClient = $this->createMock(ConfigService::class);

        $serviceCollection = new ServiceCollection();
        $serviceCollection->add(Service::create(ServicesList::FRAUD, true));

        $this->configServiceClient->method('getSite')->willReturn(
            $this->createSite(
                false,
                false,
                $serviceCollection
            )
        );
        $this->purchaseService    = $this->createMock(PurchaseService::class);
        $this->transactionService = $this->createMock(TransactionService::class);
        $transactionInfo          = $this->createMock(NewCCTransactionInformation::class);
        $transactionInfo->method('last4')->willReturn('1234');
        $transactionInfo->method('first6')->willReturn('123456');
        $this->transactionService->method('attemptCompleteThreeDTransaction')
            ->willReturn($transactionInfo);

        $this->command = new CompleteThreeDCommand($this->faker->uuid, 'pares', null);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     * @throws \Throwable
     */
    public function it_should_successfully_execute_command_and_return_complete_response()
    {
        $sessionPayload = json_decode($this->latestVersionSessionPayload(), true);

        $mainItem                                    = $sessionPayload['initializedItemCollection'][0];
        $mainItem['subscriptionId']                  = $this->faker->uuid;
        $mainItem['addonId']                         = $this->faker->uuid;
        $mainItem['bundleId']                        = $this->faker->uuid;
        $mainItem['isCrossSale']                     = false;
        $sessionPayload['initializedItemCollection'] = [
            $mainItem
        ];
        $sessionPayload['redirectUrl']               = $this->faker->url;

        $sessionPayload['memberId'] = $this->faker->uuid;
        $sessionPayload['state']    = 'threedauthenticated';

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->method('load')->willReturn(
            PurchaseProcess::restore($sessionPayload)
        );

        $handler = new CompleteThreeDCommandHandler(
            new CompleteThreeDCommandDTOAssembler($this->tokenGenerator, $this->cryptService),
            $this->transactionService,
            $processHandler,
            $this->configServiceClient,
            $this->purchaseService,
            $this->postbackMock,
            $this->biServiceMock,
            $this->createMock(EventIngestionService::class),
            $this->ccForBlackListService
        );

        /** @var CompleteThreeDCommandHandler $result */
        $result = $handler->execute($this->command);

        $this->assertInstanceOf(CompleteThreeDHttpDTO::class, $result);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     * @throws \Throwable
     */
    public function it_should_should_call_event_ingestion_system_n_times_according_feature_toggle(): void
    {
        //GIVEN
        $sessionPayload = json_decode($this->latestVersionSessionPayload(), true);

        $mainItem                   = $sessionPayload['initializedItemCollection'][0];
        $mainItem['subscriptionId'] = $this->faker->uuid;
        $mainItem['addonId']        = $this->faker->uuid;
        $mainItem['bundleId']       = $this->faker->uuid;
        $mainItem['isCrossSale']    = false;

        $sessionPayload['initializedItemCollection'] = [$mainItem];
        $sessionPayload['redirectUrl']               = $this->faker->url;
        $sessionPayload['memberId']                  = $this->faker->uuid;
        $sessionPayload['state']                     = 'threedauthenticated';

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->method('load')->willReturn(PurchaseProcess::restore($sessionPayload));

        //WHEN
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

        //THEN
        $eventIngestionSystem->expects($this->exactly($callsToEventIngestionSystem))->method('queue');

        $handler = new CompleteThreeDCommandHandler(
            new CompleteThreeDCommandDTOAssembler($this->tokenGenerator, $this->cryptService),
            $this->transactionService,
            $processHandler,
            $this->configServiceClient,
            $this->purchaseService,
            $this->postbackMock,
            $this->biServiceMock,
            $eventIngestionSystem,
            $this->ccForBlackListService
        );

        /** @var CompleteThreeDCommandHandler $result */
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

        $sessionPayload = json_decode($this->latestVersionSessionPayload(), true);

        $mainItem                                    = $sessionPayload['initializedItemCollection'][0];
        $mainItem['subscriptionId']                  = $this->faker->uuid;
        $mainItem['addonId']                         = $this->faker->uuid;
        $mainItem['bundleId']                        = $this->faker->uuid;
        $mainItem['isCrossSale']                     = false;
        $sessionPayload['initializedItemCollection'] = [
            $mainItem
        ];

        $sessionPayload['memberId'] = $this->faker->uuid;
        $sessionPayload['state']    = 'processed';

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->method('load')->willReturn(
            PurchaseProcess::restore($sessionPayload)
        );

        $handler = new CompleteThreeDCommandHandler(
            new CompleteThreeDCommandDTOAssembler($this->tokenGenerator, $this->cryptService),
            $this->transactionService,
            $processHandler,
            $this->configServiceClient,
            $this->purchaseService,
            $this->postbackMock,
            $this->biServiceMock,
            $this->createMock(EventIngestionService::class),
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
    public function it_should_throw_exception_when_missing_pares_and_md(): void
    {
        $this->expectException(MissingParesAndMdException::class);

        $sessionPayload = json_decode($this->latestVersionSessionPayload(), true);

        $mainItem                                    = $sessionPayload['initializedItemCollection'][0];
        $mainItem['subscriptionId']                  = $this->faker->uuid;
        $mainItem['addonId']                         = $this->faker->uuid;
        $mainItem['bundleId']                        = $this->faker->uuid;
        $mainItem['isCrossSale']                     = false;
        $sessionPayload['initializedItemCollection'] = [
            $mainItem
        ];
        $sessionPayload['redirectUrl']               = $this->faker->url;

        $sessionPayload['memberId'] = $this->faker->uuid;
        $sessionPayload['state']    = 'threedauthenticated';

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->method('load')->willReturn(
            PurchaseProcess::restore($sessionPayload)
        );

        $handler = new CompleteThreeDCommandHandler(
            new CompleteThreeDCommandDTOAssembler($this->tokenGenerator, $this->cryptService),
            $this->transactionService,
            $processHandler,
            $this->configServiceClient,
            $this->purchaseService,
            $this->postbackMock,
            $this->biServiceMock,
            $this->createMock(EventIngestionService::class),
            $this->ccForBlackListService
        );

        $command = new CompleteThreeDCommand($this->faker->uuid, '', null);

        $handler->execute($command);
    }

    /**
     * @test
     * @return void
     * @throws \Throwable
     * @throws \Exception
     */
    public function it_should_throw_exception_when_session_already_processed_on_second_try(): void
    {
        $this->expectException(SessionAlreadyProcessedException::class);

        $sessionPayload = json_decode($this->latestVersionSessionPayload(), true);

        $mainItem                                    = $sessionPayload['initializedItemCollection'][0];
        $mainItem['subscriptionId']                  = $this->faker->uuid;
        $mainItem['addonId']                         = $this->faker->uuid;
        $mainItem['bundleId']                        = $this->faker->uuid;
        $mainItem['isCrossSale']                     = false;
        $sessionPayload['initializedItemCollection'] = [
            $mainItem
        ];

        $sessionPayload['redirectUrl']         = $this->faker->url;
        $sessionPayload['memberId']            = $this->faker->uuid;
        $sessionPayload['state']               = 'valid';
        $sessionPayload['gatewaySubmitNumber'] = 1;

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->method('load')->willReturn(
            PurchaseProcess::restore($sessionPayload)
        );

        $handler = new CompleteThreeDCommandHandler(
            new CompleteThreeDCommandDTOAssembler($this->tokenGenerator, $this->cryptService),
            $this->transactionService,
            $processHandler,
            $this->configServiceClient,
            $this->purchaseService,
            $this->postbackMock,
            $this->biServiceMock,
            $this->createMock(EventIngestionService::class),
            $this->ccForBlackListService
        );


        $result = $handler->execute($this->command);

        $result = $result->jsonSerialize();

        $this->assertInstanceOf(SessionAlreadyProcessedException::class, $result['error']);
    }
}
