<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\PurchaseProcess;

use Exception;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\ProcessPurchaseCommand;
use Tests\UnitTestCase;

class PurchaseProcessCommandTest extends UnitTestCase
{
    /**
     * @test
     * @throws Exception
     */
    public function it_should_throw_an_exception_if_invalid_zip_received(): void
    {
        $this->expectException(\Throwable::class);

        $this->createProcessCommand(['zip' => 90210]);
    }

    /**
     * @test
     * @throws Exception
     */
    public function it_should_throw_an_exception_if_invalid_cc_number_received(): void
    {
        $this->expectException(\Throwable::class);

        $this->createProcessCommand(['ccNumber' => 12345]);
    }

    /**
     * @test
     * @throws Exception
     */
    public function it_should_return_set_payment_method(): void
    {
        $processCommand = $this->createProcessCommand(['paymentMethod' => 'paymentMethod1']);

        self::assertSame('paymentMethod1', $processCommand->paymentMethod());
    }

    /**
     * @test
     * @throws Exception
     */
    public function it_should_have_payment_method_as_optional(): void
    {
        $processCommand = $this->createProcessCommand();

        self::assertNull($processCommand->paymentMethod());
    }

    /**
     * @test
     * @throws Exception
     */
    public function it_should_return_a_purchase_process_command_when_correct_data_provided(): void
    {
        $processCommand = $this->createProcessCommand();

        self::assertInstanceOf(ProcessPurchaseCommand::class, $processCommand);
    }
}
