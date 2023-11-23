<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\PurchaseInit;

use Exception;
use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\PurchaseInitCommandResult;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidStateException;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Pending;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Valid;
use Tests\UnitTestCase;

class PurchaseInitCommandResultTest extends UnitTestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->createApplication();
    }

    /**
     * @test
     * @return PurchaseInitCommandResult
     */
    public function it_should_return_purchase_init_command_result_object()
    {
        $initCommandResult = new PurchaseInitCommandResult(
            $this->createMock(TokenGenerator::class),
            $this->createMock(CryptService::class)
        );

        $this->assertInstanceOf(PurchaseInitCommandResult::class, $initCommandResult);

        return $initCommandResult;
    }

    /**
     * @test
     * @param PurchaseInitCommandResult $initCommandResult PurchaseInitCommandResult
     * @depends it_should_return_purchase_init_command_result_object
     * @return void
     */
    public function it_should_return_the_right_values_after_object_init($initCommandResult)
    {
        $expected = [
            'sessionId'                     => null,
            'paymentProcessorType'          => 'gateway',
            'fraudAdvice'                   => [],
            'fraudRecommendation'           => [],
            'fraudRecommendationCollection' => [],
        ];

        $this->assertSame($expected, $initCommandResult->toArray());
    }

    /**
     * @test
     * @param PurchaseInitCommandResult $initCommandResult PurchaseInitCommandResult
     * @depends it_should_return_purchase_init_command_result_object
     * @return void
     */
    public function it_should_return_the_right_values_with_nudata_after_oblject_init($initCommandResult): void
    {
        $sessionId = $this->faker->uuid;
        $initCommandResult->addSessionId($sessionId);
        $initCommandResult->addNuData($this->createNuDataSettings());

        $expected = [
            'sessionId'                     => $sessionId,
            'paymentProcessorType'          => 'gateway',
            'fraudAdvice'                   => [],
            'fraudRecommendation'           => [],
            'fraudRecommendationCollection' => [],
            'nuData'                        => [
                'clientId'  => 'w-123456',
                'sessionId' => $sessionId,
            ],
        ];

        $this->assertSame($expected, $initCommandResult->toArray());
    }

    /**
     * @test
     *
     * @param PurchaseInitCommandResult $initCommandResult PurchaseInitCommandResult
     *
     * @return void
     * @throws LoggerException
     * @throws IllegalStateTransitionException
     * @throws InvalidStateException
     * @depends it_should_return_purchase_init_command_result_object
     */
    public function it_should_return_the_right_values_with_next_action_after_object_init($initCommandResult): void
    {
        $biller      = new RocketgateBiller();
        $state       = Valid::create();
        $fraudAdvice = FraudAdvice::create();
        $fraudAdvice->markForceThreeDOnInit();

        $initCommandResult->addNextAction(
            $state,
            $biller,
            $fraudAdvice
        );

        $expected = [
            'sessionId'                     => $initCommandResult->sessionId(),
            'paymentProcessorType'          => 'gateway',
            'fraudAdvice'                   => [],
            'fraudRecommendation'           => [],
            'fraudRecommendationCollection' => [],
            'nuData'                        => [
                'clientId'  => 'w-123456',
                'sessionId' => $initCommandResult->sessionId(),
            ],
            'nextAction'                    => [
                'type'   => 'renderGateway',
                'threeD' => [
                    'force3DSecure' => true,
                    'detect3DUsage' => false
                ],
            ],
        ];

        $this->assertSame($expected, $initCommandResult->toArray());
    }

    /**
     * @depends it_should_return_purchase_init_command_result_object
     * @test
     * @param $initCommandResult
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     */
    public function it_should_return_restart_process_after_hard_block_on_fraud_recommendation($initCommandResult): void
    {
        $biller      = new RocketgateBiller();
        $state       = Valid::create();
        $fraudAdvice = FraudAdvice::create();
        $fraudAdvice->markForceThreeDOnInit();
        $fraudRecommendation = FraudRecommendation::create(100, FraudRecommendation::BLOCK, '');

        $initCommandResult->addNextAction(
            $state,
            $biller,
            $fraudAdvice,
            $fraudRecommendation
        );

        $expected = [
            'sessionId'                     => $initCommandResult->sessionId(),
            'paymentProcessorType'          => 'gateway',
            'fraudAdvice'                   => [],
            'fraudRecommendation'           => [],
            'fraudRecommendationCollection' => [],
            'nuData'                        => [
                'clientId'  => 'w-123456',
                'sessionId' => $initCommandResult->sessionId(),
            ],
            'nextAction'                    => [
                'type'   => "restartProcess"
            ],
        ];

        $this->assertSame($expected, $initCommandResult->toArray());
    }

    /**
     * @test
     * @param PurchaseInitCommandResult $initCommandResult PurchaseInitCommandResult
     * @return void
     * @depends it_should_return_purchase_init_command_result_object
     * @throws IllegalStateTransitionException
     * @throws Exception
     */
    public function it_should_return_the_next_action_with_redirect_to_url_when_biller_is_third_party(
        PurchaseInitCommandResult $initCommandResult
    ): void {
        $biller      = new EpochBiller();
        $state       = Pending::create();
        $fraudAdvice = FraudAdvice::create();

        $initCommandResult->addNextAction(
            $state,
            $biller,
            $fraudAdvice
        );

        $expected = [
            'sessionId'                     => $initCommandResult->sessionId(),
            'paymentProcessorType'          => 'gateway',
            'fraudAdvice'                   => [],
            'fraudRecommendation'           => [],
            "fraudRecommendationCollection" => [],
            'nuData'                        => [
                'clientId'  => 'w-123456',
                'sessionId' => $initCommandResult->sessionId()
            ],
            'nextAction'                    => [
                'type'       => 'redirectToUrl',
                'thirdParty' => [
                    'url' => 'http://localhost/api/v1/purchase/thirdParty/redirect',
                ]
            ]
        ];

        $this->assertSame($expected, $initCommandResult->toArray());
    }
}
