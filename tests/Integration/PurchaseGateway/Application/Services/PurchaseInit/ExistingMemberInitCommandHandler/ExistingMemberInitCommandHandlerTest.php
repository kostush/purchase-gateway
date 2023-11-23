<?php

namespace Tests\Integration\PurchaseGateway\Application\Services\PurchaseInit\ExistingMemberInitCommandHandler;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Init\PurchaseInitCommandHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Exceptions\CrossSaleSiteNotExistException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\NoBillersInCascadeException;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\ExistingMemberInitCommandHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidEntrySiteSubscriptionCombinationException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidAmountException;
use Tests\IntegrationTestCase;

class ExistingMemberInitCommandHandlerTest extends IntegrationTestCase
{
    /**
     * @test
     * @return array
     * @throws InvalidAmountException
     * @throws Exception
     * @throws \Throwable
     */
    public function it_should_return_a_valid_purchase_init_dto(): array
    {
        $bundle = $this->createAndAddBundleToRepository();

        $command = $this->createInitCommand(
            [
                'site'             => $this->createSite(),
                'memberId'         => $this->faker->uuid,
                'subscriptionId'   => $this->faker->uuid,
                'entrySiteId'      => '',
                'bundleId'         => (string) $bundle->bundleId(),
                'addOnId'          => (string) $bundle->addonId(),
                'crossSaleOptions' => [
                    [
                        'bundleId' => (string) $bundle->bundleId(),
                        'addonId'  => (string) $bundle->addonId(),
                        'siteId'   => self::CROSS_SALE_SITE_ID
                    ]
                ]
            ]
        );

        /** @var ExistingMemberInitCommandHandler $handler */
        $handler = app()->make(ExistingMemberInitCommandHandler::class);

        /** @var PurchaseInitCommandHttpDTO $result */
        $result = $handler->execute($command);

        $this->assertInstanceOf(PurchaseInitCommandHttpDTO::class, $result);

        return $result->jsonSerialize();
    }

    /**
     * @test
     * @return array
     * @throws InvalidAmountException
     * @throws Exception
     * @throws \Throwable
     */
    public function it_should_throw_exception_when_both_subscription_id_and_entry_site_id_are_received(): array
    {
        $this->expectException(InvalidEntrySiteSubscriptionCombinationException::class);

        $command = $this->createInitCommand(
            [
                'memberId'         => $this->faker->uuid,
                'subscriptionId'   => $this->faker->uuid,
                'entrySiteId'      => $this->faker->uuid,
                'crossSaleOptions' => [
                    [
                        'siteId' => self::CROSS_SALE_SITE_ID
                    ]
                ]
            ]
        );

        /** @var ExistingMemberInitCommandHandler $handler */
        $handler = app()->make(ExistingMemberInitCommandHandler::class);

        $handler->execute($command);
    }

    /**
     * @test
     * @return array
     * @throws InvalidAmountException
     * @throws Exception
     * @throws \Throwable
     */
    public function it_should_throw_exception_when_both_subscription_id_and_entry_site_id_are_missing(): array
    {
        $this->expectException(InvalidEntrySiteSubscriptionCombinationException::class);

        $command = $this->createInitCommand(
            [
                'memberId'         => $this->faker->uuid,
                'subscriptionId'   => '',
                'entrySiteId'      => '',
                'crossSaleOptions' => [
                    [
                        'siteId' => self::CROSS_SALE_SITE_ID
                    ]
                ]
            ]
        );

        /** @var ExistingMemberInitCommandHandler $handler */
        $handler = app()->make(ExistingMemberInitCommandHandler::class);

        $handler->execute($command);
    }

    /**
     * @test
     * @return array
     * @throws InvalidAmountException
     * @throws Exception
     * @throws \Throwable
     */
    public function it_should_throw_exception_when_no_entry_site_id_and_subscription_id_only_on_main(): array
    {
        $this->expectException(InvalidEntrySiteSubscriptionCombinationException::class);
        $crossSales[] = [
            'siteId'         => self::CROSS_SALE_SITE_ID,
            'amount'         => $this->faker->randomFloat(2, 1, 100),
            'rebillAmount'   => $this->faker->randomFloat(2, 1, 100),
            'subscriptionId' => '',
            'initialDays'    => $this->faker->randomNumber(2),
            'rebillDays'     => $this->faker->randomNumber(2),
            'bundleId'       => $this->faker->uuid,
            'addonId'        => $this->faker->uuid,
        ];
        $command      = $this->createInitCommand(
            [
                'memberId'       => $this->faker->uuid,
                'subscriptionId' => $this->faker->uuid,
                'entrySiteId'    => '',
                'crossSales'     => $crossSales,
            ]
        );

        /** @var ExistingMemberInitCommandHandler $handler */
        $handler = app()->make(ExistingMemberInitCommandHandler::class);

        $handler->execute($command);
    }

    /**
     * @test
     * @depends it_should_return_a_valid_purchase_init_dto
     * @param array $result Result
     * @return void
     */
    public function it_should_have_the_session_id_key_in_response(array $result)
    {
        $this->assertArrayHasKey('sessionId', $result);
    }

    /**
     * @test
     * @depends it_should_return_a_valid_purchase_init_dto
     * @param array $result Result
     * @return void
     */
    public function it_should_have_the_payment_processor_type_key_in_response(array $result)
    {
        $this->assertArrayHasKey('paymentProcessorType', $result);
    }

    /**
     * @test
     * @depends it_should_return_a_valid_purchase_init_dto
     * @param array $result Result
     * @return void
     */
    public function it_should_have_the_fraud_advice_key_in_response(array $result)
    {
        $this->assertArrayHasKey('fraudAdvice', $result);
    }

    /**
     * @test
     * @depends it_should_return_a_valid_purchase_init_dto
     * @param array $result Result
     * @return void
     */
    public function it_should_have_the_fraud_recommendation_key_in_response(array $result)
    {
        $this->assertArrayHasKey('fraudRecommendation', $result);
    }

    /**
     * @test
     * @depends it_should_return_a_valid_purchase_init_dto
     * @param array $result Result
     * @return void
     */
    public function it_should_have_the_payment_template_info_key_in_response(array $result)
    {
        $this->assertArrayHasKey('paymentTemplateInfo', $result);
    }

    /**
     * @test
     * @return array
     * @throws InvalidAmountException
     * @throws Exception
     * @throws \Throwable
     */
    public function it_should_return_a_cross_sale_site_not_exist_exception_if_cross_sale_site_id_invalid(): array
    {
        $bundle = $this->createAndAddBundleToRepository();

        $command = $this->createInitCommand(
            [
                'site'             => $this->createSite(),
                'memberId'         => $this->faker->uuid,
                'subscriptionId'   => $this->faker->uuid,
                'entrySiteId'      => '',
                'bundleId'         => (string) $bundle->bundleId(),
                'addOnId'          => (string) $bundle->addonId(),
                'crossSaleOptions' => [
                    [
                        'bundleId' => (string) $bundle->bundleId(),
                        'addonId'  => (string) $bundle->addonId(),
                        'siteId'   => $this->faker->uuid
                    ]
                ]
            ]
        );

        /** @var ExistingMemberInitCommandHandler $handler */
        $handler = app()->make(ExistingMemberInitCommandHandler::class);

        $this->expectException(CrossSaleSiteNotExistException::class);

        $handler->execute($command);
    }
}
