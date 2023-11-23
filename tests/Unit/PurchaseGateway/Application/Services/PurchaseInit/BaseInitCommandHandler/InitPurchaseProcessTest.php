<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\PurchaseInit\BaseInitCommandHandler;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\BaseInitCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\PurchaseInitCommand;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Created;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidAmountException;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Services\BundleValidationService;
use Tests\UnitTestCase;

class InitPurchaseProcessTest extends UnitTestCase
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
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->command = $this->createInitCommand();

        $this->handler = $this->createMock(BaseInitCommandHandler::class);

        $reflection = new \ReflectionClass(BaseInitCommandHandler::class);

        $method = $reflection->getMethod('initPurchaseProcess');
        $method->setAccessible(true);

        $bundleValidationServiceProperty = $reflection->getProperty('bundleValidationService');
        $bundleValidationServiceProperty->setAccessible(true);
        $bundleValidationServiceProperty->setValue(
            $this->handler,
            $this->createMock(BundleValidationService::class)
        );

        $this->method = $method;
    }

    /**
     * @test
     * @return PurchaseProcess
     * @throws \ReflectionException
     */
    public function it_should_create_the_purchase_process()
    {
        $this->method->invoke($this->handler, $this->command);

        $reflection = new \ReflectionClass(BaseInitCommandHandler::class);

        $attribute = $reflection->getProperty('purchaseProcess');
        $attribute->setAccessible(true);
        $purchaseProcess = $attribute->getValue($this->handler);

        $this->assertInstanceOf(PurchaseProcess::class, $purchaseProcess);

        return $purchaseProcess;
    }

    /**
     * @test
     * @depends it_should_create_the_purchase_process
     * @param PurchaseProcess $purchaseProcess Purchase process
     * @return void
     */
    public function it_should_have_state_created(PurchaseProcess $purchaseProcess)
    {
        $this->assertInstanceOf(Created::class, $purchaseProcess->state());
    }

    /**
     * @test
     * @depends it_should_create_the_purchase_process
     * @param PurchaseProcess $purchaseProcess Purchase process
     * @return void
     */
    public function it_should_have_all_the_initialized_items(PurchaseProcess $purchaseProcess)
    {
        // One main and one cross sale were created in setup
        $this->assertEquals(2, count($purchaseProcess->initializedItemCollection()));
    }
}
