<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\PurchaseInit;

use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\BI\BILoggerService;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Init\PurchaseInitDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\PurchaseInitCommand;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\NewMemberInitCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\PurchaseInitCommandResult;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidAmountException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Service;
use ProBillerNG\PurchaseGateway\Domain\Model\ServicesList;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Services\BundleValidationService;
use ProBillerNG\PurchaseGateway\Domain\Services\CascadeService;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudService;
use ProBillerNG\PurchaseGateway\Domain\Services\NuDataService;
use ProBillerNG\PurchaseGateway\Domain\Services\RetrieveFraudRecommendationForNewMemberOnInit;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use Tests\UnitTestCase;

class NewMemberInitCommandHandlerTest extends UnitTestCase
{
    /**
     * @var PurchaseInitCommand
     */
    private $command;

    /**
     * @return void
     * @throws \ReflectionException
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidAmountException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->command = $this->createInitCommand(['crossSales' => []]);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public function it_should_call_retrieve_fraud_advice_if_fraud_should_be_checked()
    {
        $site = $this->createSite();
        $site->serviceCollection()->add(
            Service::create(ServicesList::FRAUD, true)
        );

        $command = $this->createInitCommand(['site' => $site, 'crossSaleOptions' => [['siteId' => self::CROSS_SALE_SITE_ID]]]);

        $configService = $this->createMock(ConfigService::class);
        $configService->method('getSite')->willReturn($this->createMock(Site::class));

        /** @var MockObject|NewMemberInitCommandHandler $handler */
        $handler = $this->getMockBuilder(NewMemberInitCommandHandler::class)
            ->setConstructorArgs(
                [
                    $this->createMock(CascadeService::class),
                    $this->createMock(FraudService::class),
                    $this->createMock(NuDataService::class),
                    $this->createMock(PurchaseProcessHandler::class),
                    $this->createMock(BILoggerService::class),
                    $this->createMock(PurchaseInitDTOAssembler::class),
                    $this->createMock(BundleValidationService::class),
                    $this->createMock(RetrieveFraudRecommendationForNewMemberOnInit::class),
                    $this->createMock(PurchaseInitCommandResult::class),
                    $this->createMock(EventIngestionService::class),
                    $configService
                ]
            )
            ->onlyMethods(
                [
                    'retrieveFraudAdvice',
                    'setCascade',
                    'shipBiInitializedPurchaseEvent',
                    'generatePurchaseInitResult',
                    'setNuDataSettings',
                    'createPurchaseProcess'
                ]
            )
            ->getMock();

        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $cascade         = Cascade::create(BillerCollection::buildBillerCollection([new RocketgateBiller(), new RocketgateBiller()]));
        $purchaseProcess->method('cascade')->willReturn($cascade);
        $handler->method('createPurchaseProcess')->willReturn($purchaseProcess);

        $handler->expects($this->once())->method('retrieveFraudAdvice');

        $handler->execute($command);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public function it_should_call_fraud_advice_not_required_if_service_disabled()
    {
        $site = $this->createSite();
        $site->serviceCollection()->add(
            Service::create(ServicesList::FRAUD, false)
        );

        $command = $this->createInitCommand(['site' => $site, 'crossSaleOptions' => [['siteId' => self::CROSS_SALE_SITE_ID]]]);

        $configService = $this->createMock(ConfigService::class);
        $configService->method('getSite')->willReturn($this->createMock(Site::class));

        /** @var MockObject|NewMemberInitCommandHandler $handler */
        $handler = $this->getMockBuilder(NewMemberInitCommandHandler::class)
            ->setConstructorArgs(
                [
                    $this->createMock(CascadeService::class),
                    $this->createMock(FraudService::class),
                    $this->createMock(NuDataService::class),
                    $this->createMock(PurchaseProcessHandler::class),
                    $this->createMock(BILoggerService::class),
                    $this->createMock(PurchaseInitDTOAssembler::class),
                    $this->createMock(BundleValidationService::class),
                    $this->createMock(RetrieveFraudRecommendationForNewMemberOnInit::class),
                    $this->createMock(PurchaseInitCommandResult::class),
                    $this->createMock(EventIngestionService::class),
                    $configService
                ]
            )
            ->onlyMethods(
                [
                    'fraudAdviceNotRequired',
                    'setCascade',
                    'shipBiInitializedPurchaseEvent',
                    'generatePurchaseInitResult',
                    'setNuDataSettings',
                    'createPurchaseProcess'
                ]
            )
            ->getMock();

        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $cascade         = Cascade::create(BillerCollection::buildBillerCollection([new RocketgateBiller(), new RocketgateBiller()]));
        $purchaseProcess->method('cascade')->willReturn($cascade);
        $handler->method('createPurchaseProcess')->willReturn($purchaseProcess);

        $handler->expects($this->once())->method('fraudAdviceNotRequired');

        $handler->execute($command);
    }

    /**
     * @test
     * @return void
     * @throws InvalidAmountException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public function it_should_call_fraud_advice_not_required_if_first_biller_is_third_party(): void
    {
        $site = $this->createSite();
        $site->serviceCollection()->add(
            Service::create(ServicesList::FRAUD, true)
        );

        $command = $this->createInitCommand(['site' => $site, 'crossSaleOptions' => [['siteId' => self::CROSS_SALE_SITE_ID]]]);

        $configService = $this->createMock(ConfigService::class);
        $configService->method('getSite')->willReturn($this->createMock(Site::class));

        /** @var MockObject|NewMemberInitCommandHandler $handler */
        $handler = $this->getMockBuilder(NewMemberInitCommandHandler::class)
            ->setConstructorArgs(
                [
                    $this->createMock(CascadeService::class),
                    $this->createMock(FraudService::class),
                    $this->createMock(NuDataService::class),
                    $this->createMock(PurchaseProcessHandler::class),
                    $this->createMock(BILoggerService::class),
                    $this->createMock(PurchaseInitDTOAssembler::class),
                    $this->createMock(BundleValidationService::class),
                    $this->createMock(RetrieveFraudRecommendationForNewMemberOnInit::class),
                    $this->createMock(PurchaseInitCommandResult::class),
                    $this->createMock(EventIngestionService::class),
                    $configService
                ]
            )
            ->onlyMethods(
                [
                    'fraudAdviceNotRequired',
                    'setCascade',
                    'shipBiInitializedPurchaseEvent',
                    'generatePurchaseInitResult',
                    'setNuDataSettings',
                    'createPurchaseProcess'
                ]
            )
            ->getMock();

        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $cascade         = Cascade::create(BillerCollection::buildBillerCollection([new EpochBiller(), new RocketgateBiller()]));
        $purchaseProcess->method('cascade')->willReturn($cascade);
        $handler->method('createPurchaseProcess')->willReturn($purchaseProcess);

        $handler->expects($this->once())->method('fraudAdviceNotRequired');

        $handler->execute($command);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public function it_should_call_store_on_purchase_process()
    {
        $sessionHandler = $this->createMock(PurchaseProcessHandler::class);
        $sessionHandler->expects($this->once())->method('create');

        /** @var MockObject|NewMemberInitCommandHandler $handler */
        $handler = $this->getMockBuilder(NewMemberInitCommandHandler::class)
            ->setConstructorArgs(
                [
                    $this->createMock(CascadeService::class),
                    $this->createMock(FraudService::class),
                    $this->createMock(NuDataService::class),
                    $sessionHandler,
                    $this->createMock(BILoggerService::class),
                    $this->createMock(PurchaseInitDTOAssembler::class),
                    $this->createMock(BundleValidationService::class),
                    $this->createMock(RetrieveFraudRecommendationForNewMemberOnInit::class),
                    $this->createMock(PurchaseInitCommandResult::class),
                    $this->createMock(EventIngestionService::class),
                    $this->createMock(ConfigService::class),
                ]
            )
            ->onlyMethods(
                [
                    'fraudAdviceNotRequired',
                    'setCascade',
                    'shipBiInitializedPurchaseEvent',
                    'generatePurchaseInitResult',
                    'createPurchaseProcess'
                ]
            )
            ->getMock();

        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $cascade         = Cascade::create(BillerCollection::buildBillerCollection([new RocketgateBiller(), new RocketgateBiller()]));
        $purchaseProcess->method('cascade')->willReturn($cascade);
        $handler->method('createPurchaseProcess')->willReturn($purchaseProcess);

        $handler->expects($this->once())->method('fraudAdviceNotRequired');

        $handler->execute($this->command);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public function it_should_call_store_on_purchase_process_if_exception_encountered_and_purchase_process_initialized()
    {
        $this->expectException(\Exception::class);

        $sessionHandler = $this->createMock(PurchaseProcessHandler::class);
        $sessionHandler->expects($this->once())->method('create');
        /** @var MockObject|NewMemberInitCommandHandler $handler */
        $handler = $this->getMockBuilder(NewMemberInitCommandHandler::class)
            ->setConstructorArgs(
                [
                    $this->createMock(CascadeService::class),
                    $this->createMock(FraudService::class),
                    $this->createMock(NuDataService::class),
                    $sessionHandler,
                    $this->createMock(BILoggerService::class),
                    $this->createMock(PurchaseInitDTOAssembler::class),
                    $this->createMock(BundleValidationService::class),
                    $this->createMock(RetrieveFraudRecommendationForNewMemberOnInit::class),
                    $this->createMock(PurchaseInitCommandResult::class),
                    $this->createMock(EventIngestionService::class),
                    $this->createMock(ConfigService::class),
                ]
            )
            ->setMethods(
                [
                    'fraudAdviceNotRequired',
                    'setCascade',
                    'shipBiInitializedPurchaseEvent',
                    'generatePurchaseInitResult',
                    'createPurchaseProcess'
                ]
            )
            ->getMock();

        $handler->method('generatePurchaseInitResult')->willThrowException(new \Exception());

        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $cascade         = Cascade::create(BillerCollection::buildBillerCollection([new RocketgateBiller(), new RocketgateBiller()]));
        $purchaseProcess->method('cascade')->willReturn($cascade);
        $handler->method('createPurchaseProcess')->willReturn($purchaseProcess);

        $handler->expects($this->once())->method('fraudAdviceNotRequired');

        $handler->execute($this->command);
    }


    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public function it_should_call_store_on_purchase_process_if_validation_exception_triggered()
    {
        $this->expectException(\Exception::class);

        $sessionHandler = $this->createMock(PurchaseProcessHandler::class);
        $sessionHandler->expects($this->once())->method('create');

        /** @var MockObject|NewMemberInitCommandHandler $handler */
        $handler = $this->getMockBuilder(NewMemberInitCommandHandler::class)
            ->setConstructorArgs(
                [
                    $this->createMock(CascadeService::class),
                    $this->createMock(FraudService::class),
                    $this->createMock(NuDataService::class),
                    $sessionHandler,
                    $this->createMock(BILoggerService::class),
                    $this->createMock(PurchaseInitDTOAssembler::class),
                    $this->createMock(BundleValidationService::class),
                    $this->createMock(RetrieveFraudRecommendationForNewMemberOnInit::class),
                    $this->createMock(PurchaseInitCommandResult::class),
                    $this->createMock(EventIngestionService::class),
                    $this->createMock(ConfigService::class),
                ]
            )
            ->setMethods(
                [
                    'fraudAdviceNotRequired',
                    'setCascade',
                    'shipBiInitializedPurchaseEvent',
                    'generatePurchaseInitResult',
                    'createPurchaseProcess'
                ]
            )
            ->getMock();

        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $cascade         = Cascade::create(BillerCollection::buildBillerCollection([new RocketgateBiller(), new RocketgateBiller()]));
        $purchaseProcess->method('cascade')->willReturn($cascade);
        $purchaseProcess->method('filterBillersIfThreeDSAdvised')->willThrowException(new ValidationException());

        $handler->method('createPurchaseProcess')->willReturn($purchaseProcess);

        $handler->execute($this->command);
    }


}
