<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\PurchaseInit\BaseInitCommandHandler;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\BaseInitCommandHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidAmountException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentTypeException;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use Tests\UnitTestCase;

class CreatePurchaseProcessTest extends UnitTestCase
{
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
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = $this->createMock(BaseInitCommandHandler::class);

        $reflection = new \ReflectionClass(BaseInitCommandHandler::class);

        $method = $reflection->getMethod('createPurchaseProcess');
        $method->setAccessible(true);
        $this->method = $method;
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidAmountException
     */
    public function it_should_throw_exception_if_payment_method_not_supported()
    {
        $this->expectException(UnsupportedPaymentTypeException::class);

        $command = $this->createInitCommand(['paymentType' => 'unsupported']);

        $this->method->invoke($this->handler, $command);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidAmountException
     */
    public function it_should_throw_exception_if_exception_encountered_during_creation()
    {
        $this->expectException(\Exception::class);

        $command = $this->createInitCommand(['sessionId' => 'invalid']);

        $this->method->invoke($this->handler, $command);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidAmountException
     */
    public function it_should_return_purchase_process_if_creation_successful()
    {
        $command = $this->createInitCommand();

        $purchaseProcess = $this->method->invoke($this->handler, $command);

        $this->assertInstanceOf(PurchaseProcess::class, $purchaseProcess);
    }
}
