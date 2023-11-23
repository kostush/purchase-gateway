<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidStateException;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\FinishProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\NextActionCompleteFactory;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Pending;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processed;
use Tests\UnitTestCase;

class NextActionCompleteFactoryTest extends UnitTestCase
{
    /**
     * @test
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidStateException
     * @return void
     */
    public function it_should_create_and_return_finish_process_object(): void
    {
        $state = \ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processed::create();

        $nextAction = NextActionCompleteFactory::create(
            $state
        );

        $this->assertInstanceOf(FinishProcess::class, $nextAction);
    }

    /**
     * @test
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidStateException
     * @return void
     */
    public function it_should_throw_exception_when_invalid_state_provided(): void
    {
        $this->expectException(InvalidStateException::class);

        $state = \ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Pending::create();

        NextActionCompleteFactory::create(
            $state
        );
    }
}
