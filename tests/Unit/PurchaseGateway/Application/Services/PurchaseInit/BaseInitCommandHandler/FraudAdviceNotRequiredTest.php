<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\PurchaseInit\BaseInitCommandHandler;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\BaseInitCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\PurchaseInitCommand;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidAmountException;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Valid;
use ProBillerNG\PurchaseGateway\Domain\Services\BundleValidationService;
use Tests\UnitTestCase;

class FraudAdviceNotRequiredTest extends UnitTestCase
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

        $this->command = $this->createInitCommand(['crossSales' => []]);

        $this->handler = $this->createMock(BaseInitCommandHandler::class);

        $reflection = new \ReflectionClass(BaseInitCommandHandler::class);

        $bundleValidationServiceProperty = $reflection->getProperty('bundleValidationService');
        $bundleValidationServiceProperty->setAccessible(true);
        $bundleValidationServiceProperty->setValue(
            $this->handler,
            $this->createMock(BundleValidationService::class)
        );

        $method = $reflection->getMethod('fraudAdviceNotRequired');
        $method->setAccessible(true);
        $this->method = $method;
    }

    /**
     * @test
     * @return PurchaseProcess
     * @throws \ReflectionException
     */
    public function it_should_add_empty_fraud_advice_to_purchase_process()
    {
        $reflection = new \ReflectionClass(BaseInitCommandHandler::class);

        $createProcessPurchaseMethod = $reflection->getMethod('initPurchaseProcess');
        $createProcessPurchaseMethod->setAccessible(true);

        $createProcessPurchaseMethod->invoke($this->handler, $this->command);

        $this->method->invokeArgs(
            $this->handler,
            [
                $this->command->site()->siteId(), false
            ]
        );

        $attribute = $reflection->getProperty('purchaseProcess');
        $attribute->setAccessible(true);
        $purchaseProcess = $attribute->getValue($this->handler);

        $this->assertInstanceOf(FraudAdvice::class, $purchaseProcess->fraudAdvice());

        return $purchaseProcess;
    }

    /**
     * @test
     * @depends it_should_add_empty_fraud_advice_to_purchase_process
     * @param PurchaseProcess $purchaseProcess Purchase process
     * @return void
     */
    public function it_should_have_no_ip_on_fraud_advice(PurchaseProcess $purchaseProcess)
    {
        $this->assertNull($purchaseProcess->fraudAdvice()->ip());
    }

    /**
     * @test
     * @depends it_should_add_empty_fraud_advice_to_purchase_process
     * @param PurchaseProcess $purchaseProcess Purchase process
     * @return void
     */
    public function it_should_have_no_captcha_advice(PurchaseProcess $purchaseProcess)
    {
        $this->assertFalse($purchaseProcess->fraudAdvice()->isInitCaptchaAdvised());
    }

    /**
     * @test
     * @depends it_should_add_empty_fraud_advice_to_purchase_process
     * @param PurchaseProcess $purchaseProcess Purchase process
     * @return void
     */
    public function it_should_have_no_blacklist(PurchaseProcess $purchaseProcess)
    {
        $this->assertFalse($purchaseProcess->isBlacklistedOnInit());
    }

    /**
     * @test
     * @depends it_should_add_empty_fraud_advice_to_purchase_process
     * @param PurchaseProcess $purchaseProcess Purchase process
     * @return void
     */
    public function it_should_change_state_of_process_to_valid(PurchaseProcess $purchaseProcess)
    {
        $this->assertInstanceOf(Valid::class, $purchaseProcess->state());
    }
}
