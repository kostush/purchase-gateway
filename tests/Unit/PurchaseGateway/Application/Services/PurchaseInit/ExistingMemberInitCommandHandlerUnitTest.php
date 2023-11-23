<?php

namespace Tests\Unit\PurchaseGateway\Application\Services\PurchaseInit;

use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\Base\Application\Services\Command;
use ProBillerNG\BI\BILoggerService;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Application\BI\PurchaseInitialized;
use ProBillerNG\PurchaseGateway\Application\BI\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Init\PurchaseInitDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\ExistingMemberInitCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\NewMemberInitCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\PurchaseInitCommand;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\PurchaseInitCommandResult;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\ProcessPurchaseCommand;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\Biller;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidAmountException;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Service;
use ProBillerNG\PurchaseGateway\Domain\Model\ServicesList;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Services\BundleValidationService;
use ProBillerNG\PurchaseGateway\Domain\Services\CascadeService;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudCsService;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudService;
use ProBillerNG\PurchaseGateway\Domain\Services\MemberProfileGatewayService;
use ProBillerNG\PurchaseGateway\Domain\Services\NuDataService;
use ProBillerNG\PurchaseGateway\Domain\Services\PaymentTemplateTranslatingService;
use ProBillerNG\PurchaseGateway\Domain\Services\RetrieveFraudRecommendationForExistingMemberOnInit;
use ProBillerNG\PurchaseGateway\Domain\Services\RetrieveFraudRecommendationForNewMemberOnInit;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ReflectionException;
use Tests\UnitTestCase;
use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;

class ExistingMemberInitCommandHandlerUnitTest extends UnitTestCase
{
    /**
     * @var PurchaseInitCommand
     */
    private $command;

    /**
     * @var \ReflectionClass
     */
    private $reflection;

    /**
     * @return void
     * @throws ReflectionException
     * @throws Exception
     * @throws InvalidAmountException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->command    = $this->createInitCommand(['crossSales' => []]);
        $this->reflection = new \ReflectionClass(ExistingMemberInitCommandHandler::class);
        Config::clearResolvedInstances();
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public function it_should_call_retrieve_fraud_advice_if_fraud_should_be_checked(): void
    {
        $site = $this->createSite();
        $site->serviceCollection()->add(
            Service::create(ServicesList::FRAUD, true)
        );

        $command = $this->createInitCommand(
            [
                'site'        => $site,
                'memberId'    => 'memberId',
                'paymentType' => 'cc',
                'crossSaleOptions' => [
                    [
                        'siteId' => self::CROSS_SALE_SITE_ID
                    ]
                ]
            ]
        );

        $configService = $this->createMock(ConfigService::class);
        $configService->method('getSite')->willReturn($this->createMock(Site::class));

        /** @var MockObject|ExistingMemberInitCommandHandler $handler */
        $handler = $this->getMockBuilder(ExistingMemberInitCommandHandler::class)
            ->setConstructorArgs(
                [
                    $this->createMock(CascadeService::class),
                    $this->createMock(FraudService::class),
                    $this->createMock(NuDataService::class),
                    $this->createMock(PaymentTemplateTranslatingService::class),
                    $this->createMock(FraudCsService::class),
                    $this->createMock(PurchaseProcessHandler::class),
                    $this->createMock(BILoggerService::class),
                    $this->createMock(PurchaseInitDTOAssembler::class),
                    $this->createMock(BundleValidationService::class),
                    $this->createMock(RetrieveFraudRecommendationForExistingMemberOnInit::class),
                    $this->createMock(MemberProfileGatewayService::class),
                    $this->createMock(PurchaseInitCommandResult::class),
                    $this->createMock(EventIngestionService::class),
                    $configService
                ]
            )
            ->onlyMethods(
                [
                    'retrieveFraudAdvice',
                    'createPurchaseProcess',
                    'shipBiInitializedPurchaseEvent',
                    'generatePurchaseInitResult',
                    'setPaymentTemplates',
                    'setNuDataSettings'
                ]
            )
            ->getMock();

        $handler->method('setPaymentTemplates');

        $purchaseProcess = $this->createMock(PurchaseProcess::class);

        $cascade = Cascade::create(
            BillerCollection::buildBillerCollection(
                [
                    new RocketgateBiller(), new RocketgateBiller()
                ]
            )
        );

        $purchaseProcess->method('cascade')->willReturn($cascade);
        $purchaseProcess->method('paymentTemplateCollection')->willReturn(null);
        $handler->method('createPurchaseProcess')->willReturn($purchaseProcess);

        $handler->expects($this->once())->method('retrieveFraudAdvice');

        $handler->execute($command);
    }

    /**
     * @test
     * @group event-ingestion
     */
    public function event_ingestion_should_be_called_when_flag_is_true()
    {
        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $purchaseProcess->method('cascade')->willReturn($this->createMock(Cascade::class));
        $purchaseInitialized = $this->createMock(PurchaseInitialized::class);
        $biLoggerService = $this->createMock(BILoggerService::class);

        $eventIngestionService = $this->createMock(EventIngestionService::class);
        $eventIngestionService->expects($this->once())->method('queue');

        $handler = new class($purchaseProcess, $purchaseInitialized, $biLoggerService, $eventIngestionService) extends ExistingMemberInitCommandHandler {
            public function __construct($purchaseProcess, $purchaseInitialized, $biLoggerService, $eventIngestionService){
                $this->purchaseProcess = $purchaseProcess;
                $this->purchaseInitialized = $purchaseInitialized;
                $this->biLoggerService = $biLoggerService;
                $this->eventIngestionService = $eventIngestionService;
            }
            public function execute(Command $command)
            {
                $this->shipBiInitializedPurchaseEvent();
            }

            protected function generatePurchaseInitializedEvent(
                array $mainPurchaseItem,
                array $crossSalesArray,
                ?array $paymentTemplates,
                ?array $threeD,
                ?array $fraudCollection,
                ?array $gatewayServiceFlags
            ): PurchaseInitialized
            {
                return $this->purchaseInitialized;
            }

        };

        Config::set('app.feature.event_ingestion_communication.send_general_bi_events', true);
        $handler->execute($this->command);
    }

    /**
     * @test
     * @group event-ingestion
     */
    public function event_ingestion_should_not_be_called_when_flag_is_false()
    {
        Config::set('app.feature.event_ingestion_communication.send_general_bi_events', false);
        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $purchaseProcess->method('cascade')->willReturn($this->createMock(Cascade::class));
        $purchaseInitialized = $this->createMock(PurchaseInitialized::class);
        $biLoggerService = $this->createMock(BILoggerService::class);

        $eventIngestionService = $this->createMock(EventIngestionService::class);
        $eventIngestionService->expects($this->never())->method('queue');

        $handler = new class($purchaseProcess, $purchaseInitialized, $biLoggerService, $eventIngestionService) extends ExistingMemberInitCommandHandler {
            public function __construct($purchaseProcess, $purchaseInitialized, $biLoggerService, $eventIngestionService){
                $this->purchaseProcess = $purchaseProcess;
                $this->purchaseInitialized = $purchaseInitialized;
                $this->biLoggerService = $biLoggerService;
                $this->eventIngestionService = $eventIngestionService;
            }
            public function execute(Command $command)
            {
                $this->shipBiInitializedPurchaseEvent();
            }

            protected function generatePurchaseInitializedEvent(
                array $mainPurchaseItem,
                array $crossSalesArray,
                ?array $paymentTemplates,
                ?array $threeD,
                ?array $fraudCollection,
                ?array $gatewayServiceFlags
            ): PurchaseInitialized
            {
                return $this->purchaseInitialized;
            }

        };

        $handler->execute($this->command);
    }
}
