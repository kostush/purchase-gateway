<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\PurchaseInit\BaseInitCommandHandler;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\BI\BILoggerService;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Init\PurchaseInitDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\Exceptions\MissingRedirectUrlException;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\BaseInitCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\PurchaseInitCommand;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\Biller;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidAmountException;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Services\BundleValidationService;
use ProBillerNG\PurchaseGateway\Domain\Services\CascadeService;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudService;
use ProBillerNG\PurchaseGateway\Domain\Services\NuDataService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use Tests\UnitTestCase;

class SetCascadeTest extends UnitTestCase
{
    /**
     * @var PurchaseInitCommand
     */
    private $command;

    /**
     * @var MockObject|BaseInitCommandHandler
     */
    private $handler;

    /**
     * @var \ReflectionMethod
     */
    private $method;

    /**
     * @return void
     * @throws \ReflectionException
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidAmountException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->command  = $this->createInitCommand(['crossSales' => []]);
        $cascadeService = $this->createMock(CascadeService::class);
        $cascadeService->method('retrieveCascade')->willReturn(
            Cascade::create(BillerCollection::buildBillerCollection([new RocketgateBiller()]))
        );

        $this->handler = $this->getMockBuilder(BaseInitCommandHandler::class)
            ->setConstructorArgs(
                [
                    $cascadeService,
                    $this->createMock(FraudService::class),
                    $this->createMock(NuDataService::class),
                    $this->createMock(PurchaseProcessHandler::class),
                    $this->createMock(BILoggerService::class),
                    $this->createMock(PurchaseInitDTOAssembler::class),
                    $this->createMock(BundleValidationService::class),
                    $this->createMock(EventIngestionService::class),
                    $this->createMock(ConfigService::class),
                ]
            )
            ->onlyMethods(
                [
                    'execute'
                ]
            )
            ->getMock();

        $reflection = new \ReflectionClass(BaseInitCommandHandler::class);

        $method = $reflection->getMethod('setCascade');
        $method->setAccessible(true);
        $this->method = $method;
    }

    /**
     * @test
     * @return Cascade
     * @throws \ReflectionException
     */
    public function it_should_add_cascade_to_purchase_process()
    {
        $reflection = new \ReflectionClass(BaseInitCommandHandler::class);

        $createProcessPurchaseMethod = $reflection->getMethod('initPurchaseProcess');
        $createProcessPurchaseMethod->setAccessible(true);

        $createProcessPurchaseMethod->invoke($this->handler, $this->command);

        $this->method->invoke(
            $this->handler,

            $this->command->sessionId(),
            (string) $this->command->site()->siteId(),
            (string) $this->command->site()->businessGroupId(),
            $this->command->clientCountryCode(),
            $this->command->paymentType(),
            $this->command->paymentMethod(),
            $this->command->trafficSource(),
            $this->command->forceCascade()
        );

        $attribute = $reflection->getProperty('purchaseProcess');
        $attribute->setAccessible(true);
        $purchaseProcess = $attribute->getValue($this->handler);

        $this->assertInstanceOf(Cascade::class, $purchaseProcess->cascade());

        return $purchaseProcess->cascade();
    }

    /**
     * @test
     * @depends it_should_add_cascade_to_purchase_process
     * @param Cascade $cascade Cascade
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidNextBillerException
     */
    public function it_should_store_cascade_with_correct_biller(Cascade $cascade): void
    {
        $nextBiller = $cascade->nextBiller();
        $this->assertTrue(
            $nextBiller instanceof Biller
            && (new RocketgateBiller())->name() === $nextBiller->name()
            && RocketgateBiller::class === \get_class($nextBiller)
        );
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function it_should_throw_missing_redirect_url_exception_when_first_biller_is_third_party_and_redirect_url_is_null(): void
    {
        $this->expectException(MissingRedirectUrlException::class);

        $cascadeService = $this->createMock(CascadeService::class);
        $cascadeService->method('retrieveCascade')->willReturn(
            Cascade::create(BillerCollection::buildBillerCollection([new EpochBiller()]))
        );

        $handler = $this->getMockBuilder(BaseInitCommandHandler::class)
            ->setConstructorArgs(
                [
                    $cascadeService,
                    $this->createMock(FraudService::class),
                    $this->createMock(NuDataService::class),
                    $this->createMock(PurchaseProcessHandler::class),
                    $this->createMock(BILoggerService::class),
                    $this->createMock(PurchaseInitDTOAssembler::class),
                    $this->createMock(BundleValidationService::class),
                    $this->createMock(EventIngestionService::class),
                    $this->createMock(ConfigService::class),
                ]
            )
            ->onlyMethods(
                [
                    'execute'
                ]
            )
            ->getMock();

        $reflection = new \ReflectionClass(BaseInitCommandHandler::class);

        $createProcessPurchaseMethod = $reflection->getMethod('initPurchaseProcess');
        $createProcessPurchaseMethod->setAccessible(true);

        $createProcessPurchaseMethod->invoke($handler, $this->command);

        $this->method->invoke(
            $handler,
            $this->command->sessionId(),
            (string) $this->command->site()->siteId(),
            (string) $this->command->site()->businessGroupId(),
            $this->command->clientCountryCode(),
            $this->command->paymentType(),
            $this->command->paymentMethod(),
            $this->command->trafficSource(),
            $this->command->forceCascade()
        );
    }
}
