<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\RedirectToUrl;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\RenderGatewayOtherPayments;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\BlockedDueToFraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Created;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidStateException;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\NextAction;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\NextActionInitFactory;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Pending;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processed;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\RenderGateway;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\RestartProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\QyssoBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Valid;
use Tests\UnitTestCase;

class NextActionInitFactoryTest extends UnitTestCase
{
    /**
     * @test
     * @return RenderGateway
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidStateException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_render_gateway_object(): NextAction
    {
        $state       = Valid::create();
        $biller      = new RocketgateBiller();
        $fraudAdvice = FraudAdvice::create();
        $fraudAdvice->isForceThreeD();

        $nextAction = NextActionInitFactory::create(
            $state,
            $biller,
            $fraudAdvice
        );

        $this->assertInstanceOf(RenderGateway::class, $nextAction);

        return $nextAction;
    }

    /**
     * @test
     * @param RenderGateway $nextAction Render Gateway
     * @depends it_should_return_render_gateway_object
     * @return void
     */
    public function render_gateway_object_should_have_three_d_data(RenderGateway $nextAction): void
    {
        $this->assertArrayHasKey('threeD', $nextAction->toArray());
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidStateException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_render_gateway_object_without_three_d_data(): void
    {
        $state  = Valid::create();
        $biller = new RocketgateBiller();

        $nextAction = NextActionInitFactory::create(
            $state,
            $biller,
            null
        );

        $this->assertArrayNotHasKey('threeD', $nextAction->toArray());
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidStateException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_restart_process_object(): void
    {
        $state  = BlockedDueToFraudAdvice::create();
        $biller = new RocketgateBiller();

        $nextAction = NextActionInitFactory::create(
            $state,
            $biller,
            null
        );

        $this->assertInstanceOf(RestartProcess::class, $nextAction);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidStateException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_throw_exception_when_invalid_state_provided(): void
    {
        $this->expectException(InvalidStateException::class);

        $state  = Processed::create();
        $biller = new RocketgateBiller();

        NextActionInitFactory::create(
            $state,
            $biller,
            null
        );
    }

    /**
     * @test
     * @return RenderGateway
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidStateException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_render_gateway_object_when_state_create(): NextAction
    {
        $state       = Created::create();
        $biller      = new RocketgateBiller();
        $fraudAdvice = FraudAdvice::create();
        $fraudAdvice->isForceThreeD();

        $nextAction = NextActionInitFactory::create(
            $state,
            $biller,
            $fraudAdvice
        );

        $this->assertInstanceOf(RenderGateway::class, $nextAction);

        return $nextAction;
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @throws InvalidStateException
     */
    public function it_should_return_redirect_to_url_object_when_state_pending(): void
    {
        $state  = Pending::create();
        $biller = new EpochBiller();

        $nextAction = NextActionInitFactory::create(
            $state,
            $biller,
            null,
            null,
            $this->faker->url
        );

        $this->assertInstanceOf(RedirectToUrl::class, $nextAction);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @throws InvalidStateException
     */
    public function it_should_return_redirect_to_url_object_when_state_valid(): void
    {
        $state  = Pending::create();
        $biller = new EpochBiller();

        $nextAction = NextActionInitFactory::create(
            $state,
            $biller,
            null,
            null,
            $this->faker->url
        );

        $this->assertInstanceOf(RedirectToUrl::class, $nextAction);
    }

    /**
     * @test
     * @throws InvalidStateException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     */
    public function it_should_return_render_gateway_other_payments_for_a_biller_available_payment_methods(): void
    {
        $state = Pending::create();
        $biller = new QyssoBiller();

        $nextAction = NextActionInitFactory::create(
            $state,
            $biller,
            null,
            null,
            $this->faker->url
        );

        self::assertInstanceOf(RenderGatewayOtherPayments::class, $nextAction);
    }

    /**
     * @test
     * @throws InvalidStateException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     */
    public function it_should_return_restart_process_based_on_fraud_recommendation(): void
    {
        $state       = Valid::create();
        $biller      = new RocketgateBiller();
        $fraudAdvice = FraudAdvice::create();
        $fraudRecommendation = FraudRecommendation::create(100, FraudRecommendation::BLOCK, 'message');

        $nextAction = NextActionInitFactory::create(
            $state,
            $biller,
            $fraudAdvice,
            $fraudRecommendation
        );

        $this->assertSame('restartProcess', $nextAction->type());
    }
}
