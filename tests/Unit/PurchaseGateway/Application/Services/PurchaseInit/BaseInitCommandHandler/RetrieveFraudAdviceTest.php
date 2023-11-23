<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\PurchaseInit\BaseInitCommandHandler;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\BI\BILoggerService;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Init\PurchaseInitDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\BaseInitCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\PurchaseInitCommand;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidAmountException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidIpException;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\Ip;
use ProBillerNG\PurchaseGateway\Domain\Services\BundleValidationService;
use ProBillerNG\PurchaseGateway\Domain\Services\CascadeService;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudService;
use ProBillerNG\PurchaseGateway\Domain\Services\NuDataService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use Tests\UnitTestCase;

class RetrieveFraudAdviceTest extends UnitTestCase
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
     * @throws InvalidIpException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->command = $this->createInitCommand(['crossSales' => []]);

        $fraudAdvice = FraudAdvice::create(
            Ip::create($this->command->clientIp())
        );
        $fraudAdvice->markInitCaptchaAdvised();
        $fraudAdvice->markBlacklistedOnInit();

        $fraudService = $this->createMock(FraudService::class);
        $fraudService->method('retrieveAdvice')->willReturn(
            $fraudAdvice
        );

        $this->handler = $this->getMockBuilder(BaseInitCommandHandler::class)
            ->setConstructorArgs(
                [
                    $this->createMock(CascadeService::class),
                    $fraudService,
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

        $method = $reflection->getMethod('retrieveFraudAdvice');
        $method->setAccessible(true);
        $this->method = $method;
    }

    /**
     * @test
     * @return FraudAdvice
     * @throws \ReflectionException
     */
    public function it_should_add_fraud_advice_to_purchase_process()
    {
        $reflection = new \ReflectionClass(BaseInitCommandHandler::class);

        $createProcessPurchaseMethod = $reflection->getMethod('initPurchaseProcess');
        $createProcessPurchaseMethod->setAccessible(true);

        $createProcessPurchaseMethod->invoke($this->handler, $this->command);

        $this->method->invoke(
            $this->handler,
            $this->command,
            $this->command->clientIp()
        );

        $attribute = $reflection->getProperty('purchaseProcess');
        $attribute->setAccessible(true);
        $purchaseProcess = $attribute->getValue($this->handler);

        $this->assertInstanceOf(FraudAdvice::class, $purchaseProcess->fraudAdvice());

        return $purchaseProcess->fraudAdvice();
    }

    /**
     * @test
     * @depends it_should_add_fraud_advice_to_purchase_process
     * @param FraudAdvice $fraudAdvice Fraud advice
     * @return void
     * @throws InvalidIpException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_have_correct_ip_on_fraud_advice(FraudAdvice $fraudAdvice)
    {
        $this->assertTrue(Ip::create($this->command->clientIp())->equals($fraudAdvice->ip()));
    }

    /**
     * @test
     * @depends it_should_add_fraud_advice_to_purchase_process
     * @param FraudAdvice $fraudAdvice Fraud advice
     * @return void
     */
    public function it_should_have_correct_captcha_advice(FraudAdvice $fraudAdvice)
    {
        $this->assertTrue($fraudAdvice->isInitCaptchaAdvised());
    }

    /**
     * @test
     * @depends it_should_add_fraud_advice_to_purchase_process
     * @param FraudAdvice $fraudAdvice Fraud advice
     * @return void
     */
    public function it_should_have_correct_blacklist(FraudAdvice $fraudAdvice)
    {
        $this->assertTrue($fraudAdvice->isBlacklistedOnInit());
    }

    /**
     * @test
     * @return FraudAdvice
     * @throws \ReflectionException
     */
    public function it_should_add_empty_fraud_advice_to_purchase_process_if_service_call_fails()
    {
        $fraudService = $this->createMock(FraudService::class);
        $fraudService->method('retrieveAdvice')->willThrowException(new \Exception());

        $this->handler = $this->getMockBuilder(BaseInitCommandHandler::class)
            ->setConstructorArgs(
                [
                    $this->createMock(CascadeService::class),
                    $fraudService,
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

        $createProcessPurchaseMethod->invoke($this->handler, $this->command);

        $this->method->invoke(
            $this->handler,
            $this->command,
            $this->command->clientIp()
        );

        $attribute = $reflection->getProperty('purchaseProcess');
        $attribute->setAccessible(true);
        $purchaseProcess = $attribute->getValue($this->handler);

        $this->assertInstanceOf(FraudAdvice::class, $purchaseProcess->fraudAdvice());

        return $purchaseProcess->fraudAdvice();
    }

    /**
     * @test
     * @depends it_should_add_empty_fraud_advice_to_purchase_process_if_service_call_fails
     * @param FraudAdvice $fraudAdvice Fraud advice
     * @return void
     */
    public function it_should_have_no_ip_on_fraud_advice(FraudAdvice $fraudAdvice)
    {
        $this->assertNull($fraudAdvice->ip());
    }

    /**
     * @test
     * @depends it_should_add_empty_fraud_advice_to_purchase_process_if_service_call_fails
     * @param FraudAdvice $fraudAdvice Fraud advice
     * @return void
     */
    public function it_should_have_no_captcha_advice(FraudAdvice $fraudAdvice)
    {
        $this->assertFalse($fraudAdvice->isInitCaptchaAdvised());
    }

    /**
     * @test
     * @depends it_should_add_empty_fraud_advice_to_purchase_process_if_service_call_fails
     * @param FraudAdvice $fraudAdvice Fraud advice
     * @return void
     */
    public function it_should_have_no_blacklist(FraudAdvice $fraudAdvice)
    {
        $this->assertFalse($fraudAdvice->isBlacklistedOnInit());
    }
}
