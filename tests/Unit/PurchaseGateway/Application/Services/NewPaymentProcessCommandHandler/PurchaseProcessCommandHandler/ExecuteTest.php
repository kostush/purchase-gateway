<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\NewPaymentProcessCommandHandler\PurchaseProcessCommandHandler;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\BI\BILoggerService;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseGeneralHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\ProcessPurchaseCommand;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\NewPaymentProcessCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\Purchase;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Repository\SiteRepositoryReadOnly;
use ProBillerNG\PurchaseGateway\Domain\Services\BillerMappingService;
use ProBillerNG\PurchaseGateway\Domain\Services\CascadeTranslatingService;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudAdviceService\FraudRecommendationServiceFactory;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudService;
use ProBillerNG\PurchaseGateway\Domain\Services\NuDataService;
use ProBillerNG\PurchaseGateway\Domain\Services\PostbackService;
use ProBillerNG\PurchaseGateway\Domain\Services\PurchaseService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\JsonWebToken;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Repository\InMemory\RedisRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\LaravelBinRoutingServiceFactory;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\CCForBlackList\CCForBlackListTranslatingService;
use Tests\UnitTestCase;

class ExecuteTest extends UnitTestCase
{
    /**
     * @var NewPaymentProcessCommandHandler
     */
    private $handler;

    /**
     * @var ProcessPurchaseCommand
     */
    private $command;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Cascade
     */
    private $cascadeMock;

    /**
     * setup method
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->cascadeMock = $this->createMock(Cascade::class);

        $purchaseProcessMock = $this->createMock(PurchaseProcess::class);
        $purchaseProcessMock->method('isProcessed')->willReturn(true);
        $purchaseProcessMock->method('entrySiteId')->willReturn($this->faker->uuid);

        $purchaseProcessMock->method('fraudAdvice')->willReturn(
            $this->createMock(FraudAdvice::class)
        );
        $purchaseProcessMock->method('cascade')->willReturn(
            $this->cascadeMock
        );

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->method('load')->willReturn(
        //PurchaseProcess::restore(json_decode($this->version7SessionPayload(), true))
            $purchaseProcessMock
        );

        $dto            = $this->createMock(ProcessPurchaseGeneralHttpDTO::class);
        $tokenGenerator = $this->createMock(TokenGenerator::class);
        $tokenInterface = $this->createMock(JsonWebToken::class);
        $tokenGenerator->method('generateWithPublicKey')->willReturn($tokenInterface);
        $dto->method('tokenGenerator')->willReturn($tokenGenerator);

        $dtoAssembler = $this->createMock(ProcessPurchaseDTOAssembler::class);
        $dtoAssembler->method('assemble')
            ->willReturn($dto);

        $siteRepository = $this->createMock(SiteRepositoryReadOnly::class);
        $site           = $this->createSite();
        $siteRepository->method('findSite')->willReturn($site);

        $this->handler = $this->getMockBuilder(NewPaymentProcessCommandHandler::class)
            ->setConstructorArgs(
                [
                    $this->createMock(FraudService::class),
                    $this->createMock(NuDataService::class),
                    $this->createMock(BillerMappingService::class),
                    $this->createMock(LaravelBinRoutingServiceFactory::class),
                    $this->createMock(CascadeTranslatingService::class),
                    $processHandler,
                    $this->createMock(PurchaseService::class),
                    $dtoAssembler,
                    $siteRepository,
                    $this->createMock(PostbackService::class),
                    $this->createMock(BILoggerService::class),
                    $this->createMock(TransactionService::class),
                    $this->createMock(EventIngestionService::class),
                    $this->createMock(RedisRepository::class),
                    $this->createMock(CCForBlackListTranslatingService::class),
                    $this->createMock(FraudRecommendationServiceFactory::class)
                ]
            )
            ->onlyMethods(
                [
                    'checkIfPurchaseHasBeenAlreadyProcessed',
                    'checkUserInput',
                    'addUserInfoToPurchaseProcess',
                    'setPaymentInfo',
                    'retrieveBillerMapping',
                    'retrieveRoutingCodes',
                    'checkSelectedCrossSales',
                    'shipBiProcessedPurchaseEvent',
                    'shouldSetFraudAdvice',
                    'checkReturnUrl',
                    'setIsNSFValueForEachInitializedItem'

                ]
            )
            ->getMock();

        $this->command = $this->createMock(ProcessPurchaseCommand::class);
        $this->command->method('email')->willReturn($this->faker->email);
        $this->command->method('ccNumber')->willReturn($this->faker->creditCardNumber('MasterCard'));
        $this->command->method('site')->willReturn($site);
        $this->command->method('firstName')->willReturn('firstName');
        $this->command->method('lastName')->willReturn('lastName');
        $this->command->method('country')->willReturn('countryCode');
        $this->command->method('zip')->willReturn('zip');
        $this->command->method('city')->willReturn('city');
        $this->command->method('state')->willReturn('state');


        $reflection = new \ReflectionClass(NewPaymentProcessCommandHandler::class);
        $purchase   = $reflection->getProperty('purchase');
        $purchase->setAccessible(true);
        $purchase->setValue($this->handler, $this->createMock(Purchase::class));
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public function it_should_call_check_if_purchase_can_be_processed(): void
    {
        $this->handler->expects($this->once())->method('checkIfPurchaseHasBeenAlreadyProcessed');
        $this->handler->execute($this->command);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public function it_should_call_check_user_input(): void
    {
        if (config('app.feature.common_fraud_enable_for.process.new_credit_card')) {
            //It was skipped because we don't check the fields for new fraud integration
            $this->markTestSkipped();
        }
        $this->handler->method('shouldSetFraudAdvice')->willReturn(true);
        $this->handler->expects($this->once())->method('checkUserInput');
        $this->handler->execute($this->command);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public function it_should_call_add_user_info_to_purchase_process(): void
    {
        $this->handler->expects($this->once())->method('addUserInfoToPurchaseProcess');
        $this->handler->execute($this->command);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public function it_should_call_set_payment_info(): void
    {
        $this->handler->expects($this->once())->method('setPaymentInfo');
        $this->handler->execute($this->command);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public function it_should_call_set_biller_mapping(): void
    {
        $this->handler->expects($this->once())->method('retrieveBillerMapping');
        $this->handler->execute($this->command);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public function it_should_call_set_routing_codes(): void
    {
        $this->handler->expects($this->once())->method('retrieveRoutingCodes');
        $this->handler->execute($this->command);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public function it_should_call_check_selected_cross_sales(): void
    {
        $this->handler->expects($this->once())->method('checkSelectedCrossSales');
        $this->handler->execute($this->command);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public function it_should_call_setIsNSFValueForEachInitializedItem(): void
    {
        $this->handler->expects($this->once())->method('setIsNSFValueForEachInitializedItem');
        $this->handler->execute($this->command);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public function it_should_call_check_return_url(): void
    {
        $this->handler->expects($this->once())->method('checkReturnUrl');
        $this->handler->execute($this->command);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public function it_should_call_create_purchase_entity_method_from_purchase_service(): void
    {
        $purchaseProcessMock = $this->createMock(PurchaseProcess::class);
        $purchaseProcessMock->method('isProcessed')->willReturn(false);
        $purchaseProcessMock->method('cascade')->willReturn(
            $this->cascadeMock
        );
        $purchaseProcessMock->method('fraudAdvice')->willReturn(
            $this->createMock(FraudAdvice::class)
        );

        $purchaseProcessMock->expects($this->once())->method('postProcessing');

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->method('load')->willReturn(
            $purchaseProcessMock
        );

        $purchaseService = $this->createMock(PurchaseService::class);
        $purchaseService->expects($this->once())->method('createPurchaseEntity');

        /** @var NewPaymentProcessCommandHandler|MockObject $handler */
        $handler = $this->getMockBuilder(NewPaymentProcessCommandHandler::class)
            ->setConstructorArgs(
                [
                    $this->createMock(FraudService::class),
                    $this->createMock(NuDataService::class),
                    $this->createMock(BillerMappingService::class),
                    $this->createMock(LaravelBinRoutingServiceFactory::class),
                    $this->createMock(CascadeTranslatingService::class),
                    $processHandler,
                    $purchaseService,
                    $this->createMock(ProcessPurchaseDTOAssembler::class),
                    $this->createMock(SiteRepositoryReadOnly::class),
                    $this->createMock(PostbackService::class),
                    $this->createMock(BILoggerService::class),
                    $this->createMock(TransactionService::class),
                    $this->createMock(EventIngestionService::class),
                    $this->createMock(RedisRepository::class),
                    $this->createMock(CCForBlackListTranslatingService::class),
                    $this->createMock(FraudRecommendationServiceFactory::class)
                ]
            )
            ->onlyMethods(
                [
                    'checkIfPurchaseHasBeenAlreadyProcessed',
                    'checkUserInput',
                    'addUserInfoToPurchaseProcess',
                    'setPaymentInfo',
                    'retrieveBillerMapping',
                    'retrieveRoutingCodes',
                    'checkSelectedCrossSales',
                    'shipBiProcessedPurchaseEvent',
                    'setIsNSFValueForEachInitializedItem'
                ]
            )
            ->getMock();

        $reflection = new \ReflectionClass(NewPaymentProcessCommandHandler::class);

        $purchase = $reflection->getProperty('purchase');
        $purchase->setAccessible(true);
        $purchase->setValue($handler, $this->createMock(Purchase::class));

        $handler->execute($this->command);
    }

    /**
     *
     * @test
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     * @throws \Throwable
     */
    public function it_should_call_increment_gateway_submit_number(): void
    {
        $purchaseProcessMock = $this->createMock(PurchaseProcess::class);
        $purchaseProcessMock->method('isProcessed')->willReturn(true);
        $purchaseProcessMock->method('entrySiteId')->willReturn($this->faker->uuid);
        $purchaseProcessMock->method('cascade')->willReturn(
            $this->cascadeMock
        );
        $purchaseProcessMock->method('fraudAdvice')->willReturn(
            $this->createMock(FraudAdvice::class)
        );
        $purchaseProcessMock->expects($this->once())->method('incrementGatewaySubmitNumber');

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->method('load')->willReturn(
            $purchaseProcessMock
        );

        $dto            = $this->createMock(ProcessPurchaseGeneralHttpDTO::class);
        $tokenGenerator = $this->createMock(TokenGenerator::class);
        $tokenInterface = $this->createMock(JsonWebToken::class);
        $tokenGenerator->method('generateWithPublicKey')->willReturn($tokenInterface);
        $dto->method('tokenGenerator')->willReturn($tokenGenerator);

        $dtoAssembler = $this->createMock(ProcessPurchaseDTOAssembler::class);
        $dtoAssembler->method('assemble')
            ->willReturn($dto);

        $siteRepository = $this->createMock(SiteRepositoryReadOnly::class);
        $site           = $this->createSite();
        $siteRepository->method('findSite')->willReturn($site);

        /** @var NewPaymentProcessCommandHandler|MockObject $handler */
        $handler = $this->getMockBuilder(NewPaymentProcessCommandHandler::class)
            ->setConstructorArgs(
                [
                    $this->createMock(FraudService::class),
                    $this->createMock(NuDataService::class),
                    $this->createMock(BillerMappingService::class),
                    $this->createMock(LaravelBinRoutingServiceFactory::class),
                    $this->createMock(CascadeTranslatingService::class),
                    $processHandler,
                    $this->createMock(PurchaseService::class),
                    $dtoAssembler,
                    $siteRepository,
                    $this->createMock(PostbackService::class),
                    $this->createMock(BILoggerService::class),
                    $this->createMock(TransactionService::class),
                    $this->createMock(EventIngestionService::class),
                    $this->createMock(RedisRepository::class),
                    $this->createMock(CCForBlackListTranslatingService::class),
                    $this->createMock(FraudRecommendationServiceFactory::class)
                ]
            )
            ->onlyMethods(
                [
                    'checkIfPurchaseHasBeenAlreadyProcessed',
                    'checkUserInput',
                    'addUserInfoToPurchaseProcess',
                    'setPaymentInfo',
                    'retrieveBillerMapping',
                    'retrieveRoutingCodes',
                    'checkSelectedCrossSales',
                    'shipBiProcessedPurchaseEvent',
                    'setIsNSFValueForEachInitializedItem'
                ]
            )
            ->getMock();

        $reflection = new \ReflectionClass(NewPaymentProcessCommandHandler::class);

        $purchase = $reflection->getProperty('purchase');
        $purchase->setAccessible(true);
        $purchase->setValue($handler, $this->createMock(Purchase::class));

        $handler->execute($this->command);
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     * @throws \Throwable
     */
    public function it_should_call_dto_assembler(): void
    {
        $purchaseProcessMock = $this->createMock(PurchaseProcess::class);
        $purchaseProcessMock->method('isProcessed')->willReturn(true);
        $purchaseProcessMock->method('entrySiteId')->willReturn($this->faker->uuid);
        $purchaseProcessMock->method('cascade')->willReturn(
            $this->cascadeMock
        );
        $purchaseProcessMock->method('fraudAdvice')->willReturn(
            $this->createMock(FraudAdvice::class)
        );
        $purchaseProcessMock->expects($this->once())->method('incrementGatewaySubmitNumber');

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->method('load')->willReturn(
            $purchaseProcessMock
        );

        $dto            = $this->createMock(ProcessPurchaseGeneralHttpDTO::class);
        $tokenGenerator = $this->createMock(TokenGenerator::class);
        $tokenInterface = $this->createMock(JsonWebToken::class);
        $tokenGenerator->method('generateWithPublicKey')->willReturn($tokenInterface);
        $dto->method('tokenGenerator')->willReturn($tokenGenerator);

        $dtoAssemblerMock = $this->createMock(ProcessPurchaseDTOAssembler::class);
        $dtoAssemblerMock->expects($this->once())->method('assemble')->willReturn($dto);

        $siteRepository = $this->createMock(SiteRepositoryReadOnly::class);
        $site           = $this->createSite();
        $siteRepository->method('findSite')->willReturn($site);

        /** @var NewPaymentProcessCommandHandler|MockObject $handler */
        $handler = $this->getMockBuilder(NewPaymentProcessCommandHandler::class)
            ->setConstructorArgs(
                [
                    $this->createMock(FraudService::class),
                    $this->createMock(NuDataService::class),
                    $this->createMock(BillerMappingService::class),
                    $this->createMock(LaravelBinRoutingServiceFactory::class),
                    $this->createMock(CascadeTranslatingService::class),
                    $processHandler,
                    $this->createMock(PurchaseService::class),
                    $dtoAssemblerMock,
                    $siteRepository,
                    $this->createMock(PostbackService::class),
                    $this->createMock(BILoggerService::class),
                    $this->createMock(TransactionService::class),
                    $this->createMock(EventIngestionService::class),
                    $this->createMock(RedisRepository::class),
                    $this->createMock(CCForBlackListTranslatingService::class),
                    $this->createMock(FraudRecommendationServiceFactory::class)
                ]
            )
            ->onlyMethods(
                [
                    'checkIfPurchaseHasBeenAlreadyProcessed',
                    'checkUserInput',
                    'addUserInfoToPurchaseProcess',
                    'setPaymentInfo',
                    'retrieveBillerMapping',
                    'retrieveRoutingCodes',
                    'checkSelectedCrossSales',
                    'shipBiProcessedPurchaseEvent',
                    'setIsNSFValueForEachInitializedItem'
                ]
            )
            ->getMock();

        $reflection = new \ReflectionClass(NewPaymentProcessCommandHandler::class);

        $purchase = $reflection->getProperty('purchase');
        $purchase->setAccessible(true);
        $purchase->setValue($handler, $this->createMock(Purchase::class));

        $handler->execute($this->command);
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     * @throws \Throwable
     */
    public function it_should_return_a_dto(): void
    {
        $purchaseProcessMock = $this->createMock(PurchaseProcess::class);
        $purchaseProcessMock->method('entrySiteId')->willReturn($this->faker->uuid);
        $purchaseProcessMock->method('isProcessed')->willReturn(true);
        $purchaseProcessMock->method('cascade')->willReturn(
            $this->cascadeMock
        );
        $purchaseProcessMock->method('fraudAdvice')->willReturn(
            $this->createMock(FraudAdvice::class)
        );

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->method('load')->willReturn(
            $purchaseProcessMock
        );

        $dto            = $this->createMock(ProcessPurchaseGeneralHttpDTO::class);
        $tokenGenerator = $this->createMock(TokenGenerator::class);
        $tokenInterface = $this->createMock(JsonWebToken::class);
        $tokenGenerator->method('generateWithPublicKey')->willReturn($tokenInterface);
        $dto->method('tokenGenerator')->willReturn($tokenGenerator);

        $dtoAssemblerMock = $this->createMock(ProcessPurchaseDTOAssembler::class);
        $dtoAssemblerMock->expects($this->once())->method('assemble')->willReturn($dto);

        $siteRepository = $this->createMock(SiteRepositoryReadOnly::class);
        $site           = $this->createSite();
        $siteRepository->method('findSite')->willReturn($site);

        /** @var NewPaymentProcessCommandHandler|MockObject $handler */
        $handler = $this->getMockBuilder(NewPaymentProcessCommandHandler::class)
            ->setConstructorArgs(
                [
                    $this->createMock(FraudService::class),
                    $this->createMock(NuDataService::class),
                    $this->createMock(BillerMappingService::class),
                    $this->createMock(LaravelBinRoutingServiceFactory::class),
                    $this->createMock(CascadeTranslatingService::class),
                    $processHandler,
                    $this->createMock(PurchaseService::class),
                    $dtoAssemblerMock,
                    $siteRepository,
                    $this->createMock(PostbackService::class),
                    $this->createMock(BILoggerService::class),
                    $this->createMock(TransactionService::class),
                    $this->createMock(EventIngestionService::class),
                    $this->createMock(RedisRepository::class),
                    $this->createMock(CCForBlackListTranslatingService::class),
                    $this->createMock(FraudRecommendationServiceFactory::class)
                ]
            )
            ->onlyMethods(
                [
                    'checkIfPurchaseHasBeenAlreadyProcessed',
                    'checkUserInput',
                    'addUserInfoToPurchaseProcess',
                    'setPaymentInfo',
                    'retrieveBillerMapping',
                    'retrieveRoutingCodes',
                    'checkSelectedCrossSales',
                    'shipBiProcessedPurchaseEvent',
                    'setIsNSFValueForEachInitializedItem'
                ]
            )
            ->getMock();

        $reflection = new \ReflectionClass(NewPaymentProcessCommandHandler::class);

        $purchase = $reflection->getProperty('purchase');
        $purchase->setAccessible(true);
        $purchase->setValue($handler, $this->createMock(Purchase::class));

        $dto = $handler->execute($this->command);
        $this->assertInstanceOf(ProcessPurchaseGeneralHttpDTO::class, $dto);
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function set_md5_customer_email_as_merchant_customer_id_when_rocketgate_and_is_sticky_gateway_enable(): void
    {
        $purchaseProcessMock = $this->createMock(PurchaseProcess::class);
        $purchaseProcessMock->method('entrySiteId')->willReturn($this->faker->uuid);
        $purchaseProcessMock->method('isProcessed')->willReturn(true);
        $purchaseProcessMock->method('cascade')->willReturn(
            Cascade::create(
                BillerCollection::buildBillerCollection(
                    [
                        new RocketgateBiller()
                    ]
                ),
                new RocketgateBiller()
            )
        );

        $purchaseProcessMock->method('fraudAdvice')->willReturn($this->createMock(FraudAdvice::class));

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->method('load')->willReturn(
            $purchaseProcessMock
        );

        $dto            = $this->createMock(ProcessPurchaseGeneralHttpDTO::class);
        $tokenGenerator = $this->createMock(TokenGenerator::class);
        $tokenInterface = $this->createMock(JsonWebToken::class);
        $tokenGenerator->method('generateWithPublicKey')->willReturn($tokenInterface);
        $dto->method('tokenGenerator')->willReturn($tokenGenerator);

        $dtoAssemblerMock = $this->createMock(ProcessPurchaseDTOAssembler::class);
        $dtoAssemblerMock->expects($this->once())->method('assemble')->willReturn($dto);

        $siteRepository = $this->createMock(SiteRepositoryReadOnly::class);
        $site           = $this->createSite(true);
        $siteRepository->method('findSite')->willReturn($site);

        $email   = 'test@test.probiller.com';
        $command = $this->createMock(ProcessPurchaseCommand::class);
        $command->method('email')->willReturn($email);
        $command->method('site')->willReturn($site);

        /** @var NewPaymentProcessCommandHandler|MockObject $handler */
        $handler = $this->getMockBuilder(NewPaymentProcessCommandHandler::class)
            ->setConstructorArgs(
                [
                    $this->createMock(FraudService::class),
                    $this->createMock(NuDataService::class),
                    $this->createMock(BillerMappingService::class),
                    $this->createMock(LaravelBinRoutingServiceFactory::class),
                    $this->createMock(CascadeTranslatingService::class),
                    $processHandler,
                    $this->createMock(PurchaseService::class),
                    $dtoAssemblerMock,
                    $siteRepository,
                    $this->createMock(PostbackService::class),
                    $this->createMock(BILoggerService::class),
                    $this->createMock(TransactionService::class),
                    $this->createMock(EventIngestionService::class),
                    $this->createMock(RedisRepository::class),
                    $this->createMock(CCForBlackListTranslatingService::class),
                    $this->createMock(FraudRecommendationServiceFactory::class)
                ]
            )
            ->onlyMethods(
                [
                    'checkIfPurchaseHasBeenAlreadyProcessed',
                    'checkUserInput',
                    'addUserInfoToPurchaseProcess',
                    'setPaymentInfo',
                    'retrieveBillerMapping',
                    'retrieveRoutingCodes',
                    'checkSelectedCrossSales',
                    'shipBiProcessedPurchaseEvent',
                    'setIsNSFValueForEachInitializedItem'
                ]
            )
            ->getMock();

        $billerMapping          = $this->createMock(BillerMapping::class);
        $rocketgateBillerFields = RocketgateBillerFields::create(
            $_ENV['ROCKETGATE_MERCHANT_ID_3'],
            $_ENV['ROCKETGATE_MERCHANT_PASSWORD_3'],
            '8000',
            'sharedSecret',
            true
        );
        $billerMapping->method('billerFields')->willReturn($rocketgateBillerFields);
        $handler->method('retrieveBillerMapping')->willReturn($billerMapping);

        $reflection = new \ReflectionClass(NewPaymentProcessCommandHandler::class);
        $purchase   = $reflection->getProperty('purchase');
        $purchase->setAccessible(true);
        $purchase->setValue($handler, $this->createMock(Purchase::class));

        $handler->execute($command);
        $this->assertEquals(md5($email), $billerMapping->billerFields()->merchantCustomerId());
    }
}
