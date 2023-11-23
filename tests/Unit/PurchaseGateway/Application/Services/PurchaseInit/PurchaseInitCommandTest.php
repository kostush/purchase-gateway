<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\PurchaseInit;

use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\PurchaseInitCommand;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidAmountException;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use Tests\UnitTestCase;

class PurchaseInitCommandTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws InvalidAmountException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidDaysException
     */
    public function it_should_throw_an_invalid_amount_exception_if_invalid_amount_received()
    {
        $this->expectException(InvalidAmountException::class);

        $this->createInitCommand(['amount' => 'invalid']);
    }

    /**
     * @test
     * @return PurchaseInitCommand
     * @throws InvalidAmountException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Exception
     */
    public function it_should_return_a_purchase_init_command_when_correct_data_provided(): PurchaseInitCommand
    {
        $initCommand = new PurchaseInitCommand(
            $this->createSite(),
            2.1,
            10,
            30,
            39.99,
            'USD',
            '6c87e1a3-2b8d-406e-b610-e2867fd68e38',
            '810e903f-9648-4ae1-a752-6e8b3fd85869',
            '127.0.0.1',
            'cc',
            'CA',
            '0c33e1be-a246-463c-8f25-6e8987f6f389',
            'AtlasCode',
            'AtlasData',
            1,
            [],
            [],
            false,
            '1b153ebe-4286-48de-9653-4fa07bdd359b',
            '2ce90e41-1ef6-4461-b5e6-a468cc2a7e5f',
            'acfc7ede-eaa1-4a9f-8881-0277de317599',
            null,
            'Visa',
            'ALL',
            null,
            null,
            [],
            false
        );

        $this->assertInstanceOf(PurchaseInitCommand::class, $initCommand);

        return $initCommand;
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_init_command_when_correct_data_provided
     * @param PurchaseInitCommand $initCommand Purchase init command
     * @return void
     */
    public function it_should_contain_a_site(PurchaseInitCommand $initCommand): void
    {
        $this->assertInstanceOf(Site::class, $initCommand->site());
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_init_command_when_correct_data_provided
     * @param PurchaseInitCommand $initCommand Purchase init command
     * @return void
     */
    public function it_should_contain_the_correct_ammount(PurchaseInitCommand $initCommand): void
    {
        $this->assertSame($initCommand->amount(), 2.1);
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_init_command_when_correct_data_provided
     * @param PurchaseInitCommand $initCommand Purchase init command
     * @return void
     */
    public function it_should_contain_the_correct_initial_days(PurchaseInitCommand $initCommand): void
    {
        $this->assertSame($initCommand->initialDays(), 10);
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_init_command_when_correct_data_provided
     * @param PurchaseInitCommand $initCommand Purchase init command
     * @return void
     */
    public function it_should_contain_the_correct_rebill_days(PurchaseInitCommand $initCommand): void
    {
        $this->assertSame($initCommand->rebillDays(), 30);
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_init_command_when_correct_data_provided
     * @param PurchaseInitCommand $initCommand Purchase init command
     * @return void
     */
    public function it_should_contain_the_correct_curency(PurchaseInitCommand $initCommand): void
    {
        $this->assertSame($initCommand->currency(), 'USD');
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_init_command_when_correct_data_provided
     * @param PurchaseInitCommand $initCommand Purchase init command
     * @return void
     */
    public function it_should_contain_the_correct_bundle_id(PurchaseInitCommand $initCommand): void
    {
        $this->assertSame($initCommand->bundleId(), '6c87e1a3-2b8d-406e-b610-e2867fd68e38');
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_init_command_when_correct_data_provided
     * @param PurchaseInitCommand $initCommand Purchase init command
     * @return void
     */
    public function it_should_contain_the_correct_addon_id(PurchaseInitCommand $initCommand): void
    {
        $this->assertSame($initCommand->addOnId(), '810e903f-9648-4ae1-a752-6e8b3fd85869');
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_init_command_when_correct_data_provided
     * @param PurchaseInitCommand $initCommand Purchase init command
     * @return void
     */
    public function it_should_contain_the_correct_client_ip(PurchaseInitCommand $initCommand): void
    {
        $this->assertSame($initCommand->clientIp(), '127.0.0.1');
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_init_command_when_correct_data_provided
     * @param PurchaseInitCommand $initCommand Purchase init command
     * @return void
     */
    public function it_should_contain_the_correct_payment_type(PurchaseInitCommand $initCommand): void
    {
        $this->assertSame($initCommand->paymentType(), 'cc');
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_init_command_when_correct_data_provided
     * @param PurchaseInitCommand $initCommand Purchase init command
     * @return void
     */
    public function it_should_contain_the_correct_client_country_code(PurchaseInitCommand $initCommand): void
    {
        $this->assertSame($initCommand->clientCountryCode(), 'CA');
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_init_command_when_correct_data_provided
     * @param PurchaseInitCommand $initCommand Purchase init command
     * @return void
     */
    public function it_should_contain_the_correct_session_id(PurchaseInitCommand $initCommand): void
    {
        $this->assertSame($initCommand->sessionId(), '0c33e1be-a246-463c-8f25-6e8987f6f389');
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_init_command_when_correct_data_provided
     * @param PurchaseInitCommand $initCommand Purchase init command
     * @return void
     */
    public function it_should_contain_the_correct_atlas_data(PurchaseInitCommand $initCommand): void
    {
        $this->assertSame($initCommand->atlasData(), 'AtlasData');
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_init_command_when_correct_data_provided
     * @param PurchaseInitCommand $initCommand Purchase init command
     * @return void
     */
    public function it_should_contain_the_correct_atlas_code(PurchaseInitCommand $initCommand): void
    {
        $this->assertSame($initCommand->atlasCode(), 'AtlasCode');
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_init_command_when_correct_data_provided
     * @param PurchaseInitCommand $initCommand Purchase init command
     * @return void
     */
    public function it_should_contain_the_correct_public_key_index(PurchaseInitCommand $initCommand): void
    {
        $this->assertSame($initCommand->publicKeyIndex(), 1);
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_init_command_when_correct_data_provided
     * @param PurchaseInitCommand $initCommand Purchase init command
     * @return void
     */
    public function it_should_contain_the_correct_tax(PurchaseInitCommand $initCommand): void
    {
        $this->assertSame($initCommand->tax(), []);
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_init_command_when_correct_data_provided
     * @param PurchaseInitCommand $initCommand Purchase init command
     * @return void
     */
    public function it_should_contain_the_correct_cross_sales(PurchaseInitCommand $initCommand): void
    {
        $this->assertSame($initCommand->crossSales(), []);
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_init_command_when_correct_data_provided
     * @param PurchaseInitCommand $initCommand Purchase init command
     * @return void
     */
    public function it_should_contain_the_correct_is_trial(PurchaseInitCommand $initCommand): void
    {
        $this->assertFalse($initCommand->isTrial());
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_init_command_when_correct_data_provided
     * @param PurchaseInitCommand $initCommand Purchase init command
     * @return void
     */
    public function it_should_contain_the_correct_member_id(PurchaseInitCommand $initCommand): void
    {
        $this->assertSame($initCommand->memberId(), '1b153ebe-4286-48de-9653-4fa07bdd359b');
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_init_command_when_correct_data_provided
     * @param PurchaseInitCommand $initCommand Purchase init command
     * @return void
     */
    public function it_should_contain_the_correct_subscription_id(PurchaseInitCommand $initCommand): void
    {
        $this->assertSame($initCommand->subscriptionId(), '2ce90e41-1ef6-4461-b5e6-a468cc2a7e5f');
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_init_command_when_correct_data_provided
     * @param PurchaseInitCommand $initCommand Purchase init command
     * @return void
     */
    public function it_should_contain_the_correct_entry_site_id(PurchaseInitCommand $initCommand): void
    {
        $this->assertSame($initCommand->entrySiteId(), 'acfc7ede-eaa1-4a9f-8881-0277de317599');
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_init_command_when_correct_data_provided
     * @param PurchaseInitCommand $initCommand Purchase init command
     * @return void
     */
    public function it_should_contain_the_correct_force_cascade(PurchaseInitCommand $initCommand): void
    {
        $this->assertNull($initCommand->forceCascade());
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_init_command_when_correct_data_provided
     * @param PurchaseInitCommand $initCommand Purchase init command
     * @return void
     */
    public function it_should_contain_the_correct_payment_method_type(PurchaseInitCommand $initCommand): void
    {
        $this->assertSame($initCommand->paymentMethod(), 'Visa');
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_init_command_when_correct_data_provided
     * @param PurchaseInitCommand $initCommand Purchase init command
     * @return void
     */
    public function it_should_contain_the_correct_traffic_source(PurchaseInitCommand $initCommand): void
    {
        $this->assertSame($initCommand->trafficSource(), 'ALL');
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_init_command_when_correct_data_provided
     * @param PurchaseInitCommand $initCommand Purchase init command
     * @return void
     */
    public function it_should_contain_the_fraud_headers_as_empty_array_when_no_header_matches(PurchaseInitCommand $initCommand): void
    {
        $this->assertSame([], $initCommand->fraudHeaders());
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_init_command_when_correct_data_provided
     * @param PurchaseInitCommand $initCommand Purchase init command
     * @return void
     * @throws InvalidAmountException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidDaysException
     * @throws \Exception
     */
    public function it_should_contain_the_fraud_headers(PurchaseInitCommand $initCommand): void
    {
        $initCommand = new PurchaseInitCommand(
            $this->createSite(),
            2.1,
            10,
            30,
            39.99,
            'USD',
            '6c87e1a3-2b8d-406e-b610-e2867fd68e38',
            '810e903f-9648-4ae1-a752-6e8b3fd85869',
            '127.0.0.1',
            'cc',
            'CA',
            '0c33e1be-a246-463c-8f25-6e8987f6f389',
            'AtlasCode',
            'AtlasData',
            1,
            [],
            [],
            false,
            '1b153ebe-4286-48de-9653-4fa07bdd359b',
            '2ce90e41-1ef6-4461-b5e6-a468cc2a7e5f',
            'acfc7ede-eaa1-4a9f-8881-0277de317599',
            null,
            'Visa',
            'ALL',
            null,
            null,
            ['anonymousType' => 'test'],
            false
        );

        $this->assertSame(['anonymousType' => 'test'], $initCommand->fraudHeaders());
    }
}
