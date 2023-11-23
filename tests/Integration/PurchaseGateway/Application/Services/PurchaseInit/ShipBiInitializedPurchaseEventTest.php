<?php
declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Application\Services\PurchaseInit;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\BI\BILoggerService;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Init\PurchaseInitDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\PurchaseInitCommand;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\NewMemberInitCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\PurchaseInitCommandResult;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidAmountException;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Services\BundleValidationService;
use ProBillerNG\PurchaseGateway\Domain\Services\CascadeService;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudService;
use ProBillerNG\PurchaseGateway\Domain\Services\NuDataService;
use ProBillerNG\PurchaseGateway\Domain\Services\RetrieveFraudRecommendationForNewMemberOnInit;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use Tests\IntegrationTestCase;

class ShipBiInitializedPurchaseEventTest extends IntegrationTestCase
{
    /**
     * @var PurchaseInitCommand
     */
    private $command;

    /**
     * @var MockObject|NewMemberInitCommandHandler
     */
    private $handler;

    /**
     * @var \ReflectionMethod
     */
    private $method;

    /**
     * @return void
     * @throws \ReflectionException
     * @throws Exception
     * @throws InvalidAmountException
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->command = $this->createInitCommand(['crossSales' => []]);

        $biLogger = $this->createMock(BILoggerService::class);
        $biLogger->expects($this->once())->method('write');

        $configService = $this->createMock(ConfigService::class);
        $configService->method('getSite')->willReturn($this->createMock(Site::class));

        $this->handler = $this->getMockBuilder(NewMemberInitCommandHandler::class)
            ->setConstructorArgs(
                [
                    $this->createMock(CascadeService::class),
                    $this->createMock(FraudService::class),
                    $this->createMock(NuDataService::class),
                    $this->createMock(PurchaseProcessHandler::class),
                    $biLogger,
                    $this->createMock(PurchaseInitDTOAssembler::class),
                    $this->createMock(BundleValidationService::class),
                    $this->createMock(RetrieveFraudRecommendationForNewMemberOnInit::class),
                    $this->createMock(PurchaseInitCommandResult::class),
                    $this->createMock(EventIngestionService::class),
                    $configService
                ]
            )
            ->setMethods(null)
            ->getMock();

        $reflection = new \ReflectionClass(NewMemberInitCommandHandler::class);

        $method = $reflection->getMethod('shipBiInitializedPurchaseEvent');
        $method->setAccessible(true);
        $this->method = $method;
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function it_should_write_bi_event()
    {
        $reflection = new \ReflectionClass(NewMemberInitCommandHandler::class);

        $createProcessPurchaseMethod = $reflection->getMethod('initPurchaseProcess');
        $createProcessPurchaseMethod->setAccessible(true);

        /** @var PurchaseProcess $purchaseProcess */
        $createProcessPurchaseMethod->invoke($this->handler, $this->command);

        $attribute = $reflection->getProperty('purchaseProcess');
        $attribute->setAccessible(true);

        $purchaseProcess = $attribute->getValue($this->handler);
        $purchaseProcess->setCascade(
            Cascade::create(BillerCollection::buildBillerCollection([new RocketgateBiller()]))
        );
        $purchaseProcess->setFraudAdvice(FraudAdvice::create());

        $attribute->setValue($this->handler, $purchaseProcess);

        $this->method->invoke($this->handler);
    }
}
