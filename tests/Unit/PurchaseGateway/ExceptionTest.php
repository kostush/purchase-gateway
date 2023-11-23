<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway;

use ProBillerNG\PurchaseGateway\Exception;
use Tests\UnitTestCase;

class ExceptionTest extends UnitTestCase
{
    /**
     * @test
     * @throws \ReflectionException
     * @return void
     */
    public function it_should_build_the_exact_message_based_on_the_arguments_provided()
    {
        $reflection = new \ReflectionClass(Exception::class);

        $method = $reflection->getMethod('buildMessage');
        $method->setAccessible(true);

        $message = $method->invoke($this->createMock(Exception::class), ['firstParam', 'secondParam']);

        $this->assertSame('Purchase Gateway exception! - Info: [firstParam], [secondParam]', $message);
    }
}
