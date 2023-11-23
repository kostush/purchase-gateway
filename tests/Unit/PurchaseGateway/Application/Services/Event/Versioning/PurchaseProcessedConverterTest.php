<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Application\Services\Event\Versioning;

use ProBillerNG\PurchaseGateway\Application\Services\Event\Versioning\PurchaseProcessedConverter;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use Tests\UnitTestCase;

class PurchaseProcessedConverterTest extends UnitTestCase
{
    /**
     * @test
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\DomainEventConversionException
     * @return void
     */
    public function it_should_skip_conversion_and_return_the_same_payload(): void
    {
        $payload = [
            'version'      => 999999,
            'amount'       => 9.99,
            'rebill_amount' => 59.98
        ];

        $purchaseProcessedConverter = new PurchaseProcessedConverter();

        $result = $purchaseProcessedConverter->convert($payload);

        $this->assertSame($payload, $result);
    }

    /**
     * @test
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\DomainEventConversionException
     * @return void
     */
    public function it_should_convert_form_version_1_to_version_2(): void
    {
        $purchaseProcessedConverter = new PurchaseProcessedConverter();

        $result = $purchaseProcessedConverter->convert(
            [
                'version'      => 1,
                'amount'       => 11.00,
                'rebill_amount' => 61.00
            ]
        );

        $this->assertArrayHasKey('amount', $result);
        $this->assertArrayHasKey('rebillAmount', $result);
    }

    /**
     * @test
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\DomainEventConversionException
     * @return void
     */
    public function it_should_contain_the_last_changes_since_second_version(): void
    {
        $purchaseProcessedConverter = new PurchaseProcessedConverter();

        $result = $purchaseProcessedConverter->convert(
            [
                'version'       => 2,
                'amounts'       => [
                    'initialAmount' => [
                        'beforeTaxes' => 9.99,
                        'taxes'       => 1.1,
                        'afterTaxes'  => 11.00,
                    ],
                    'rebillAmount'  => [
                        'beforeTaxes' => 59.00,
                        'taxes'       => 1.1,
                        'afterTaxes'  => 61.00,
                    ]
                ]
            ]
        );

        $this->assertArrayHasKey('amount', $result);
        $this->assertArrayHasKey('rebillAmount', $result);
    }

    /**
     * @test
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\DomainEventConversionException
     * @return array
     */
    public function it_should_convert_form_version_3_to_version_4(): array
    {
        $purchaseProcessedConverter = new PurchaseProcessedConverter();

        $result = $purchaseProcessedConverter->convert(
            [
                'version'      => 3,
                'transaction_id' => $this->faker->uuid,
                'status'        => 'approved',
                'cross_sale_purchase_data' => [
                    0 => [
                        'transactionId' => $this->faker->uuid,
                        'status'        => 'approved',
                        'addOnId'       => $this->faker->uuid,
                        'amount'        => $this->faker->randomFloat()
                    ]
                ]
            ]
        );

        $this->assertArrayHasKey('transaction_collection', $result);
        return $result;
    }

    /**
     * @test
     * @depends it_should_convert_form_version_3_to_version_4
     * @param array $payload The converted payload
     * @return void
     */
    public function it_should_add_a_transaction_collection_on_version_4(array $payload): void
    {
        $this->assertArrayHasKey('transaction_collection', $payload);
    }

    /**
     * @test
     * @depends it_should_convert_form_version_3_to_version_4
     * @param array $payload The converted payload
     * @return void
     */
    public function it_should_add_a_transaction_collection_for_cross_sales_on_version_4(array $payload): void
    {
        $this->assertArrayHasKey('transactionCollection', $payload['cross_sale_purchase_data'][0]);
    }

    /**
     * @test
     * @depends it_should_convert_form_version_3_to_version_4
     * @param array $payload The converted payload
     * @return void
     */
    public function it_should_rename_addonId_for_cross_sales_on_version_4(array $payload): void
    {
        $this->assertArrayHasKey('addonId', $payload['cross_sale_purchase_data'][0]);
        $this->assertArrayNotHasKey('addOnId', $payload['cross_sale_purchase_data'][0]);
    }

    /**
     * @test
     * @depends it_should_convert_form_version_3_to_version_4
     * @param array $payload The converted payload
     * @return void
     */
    public function it_should_add_cross_sale_flag_for_cross_sales_on_version_4(array $payload): void
    {
        $this->assertArrayHasKey('isCrossSale', $payload['cross_sale_purchase_data'][0]);
    }

    /**
     * @test
     * @depends it_should_convert_form_version_3_to_version_4
     * @param array $payload The converted payload
     * @return void
     */
    public function it_should_remove_selected_cross_sales_on_version_4(array $payload): void
    {
        $this->assertArrayNotHasKey('selectedCrossSales', $payload);
    }

    /**
     * @test
     * @depends it_should_convert_form_version_3_to_version_4
     * @param array $payload The converted payload
     * @return void
     */
    public function it_should_add_a_tax_key_for_cross_sales_on_version_4(array $payload): void
    {
        $this->assertArrayHasKey('tax', $payload['cross_sale_purchase_data'][0]);
    }

    /**
     * @test
     * @depends it_should_convert_form_version_3_to_version_4
     * @param array $payload The converted payload
     * @return void
     */
    public function it_should_add_a_initial_amount_key_for_cross_sales_on_version_4(array $payload): void
    {
        $this->assertArrayHasKey('initialAmount', $payload['cross_sale_purchase_data'][0]);
    }

    /**
     * @test
     * @depends it_should_convert_form_version_3_to_version_4
     * @param array $payload The converted payload
     * @return void
     */
    public function it_should_add_a_rebill_days_key_for_cross_sales_on_version_4(array $payload): void
    {
        $this->assertArrayHasKey('rebillDays', $payload['cross_sale_purchase_data'][0]);
    }

    /**
     * @test
     * @depends it_should_convert_form_version_3_to_version_4
     * @param array $payload The converted payload
     * @return void
     */
    public function it_should_add_a_initial_days_key_for_cross_sales_on_version_4(array $payload): void
    {
        $this->assertArrayHasKey('initialDays', $payload['cross_sale_purchase_data'][0]);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\DomainEventConversionException
     */
    public function it_should_change_transaction_state_of_main_purchase_to_aborted_if_failed_and_no_transaction_id_present()
    {
        $purchaseProcessedConverter = new PurchaseProcessedConverter();

        $result = $purchaseProcessedConverter->convert(
            [
                'version'                  => 3,
                'transaction_id'           => '',
                'status'                   => 'failed',
            ]
        );

        $this->assertEquals(Transaction::STATUS_ABORTED, $result['transaction_collection'][0]['state']);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\DomainEventConversionException
     */
    public function it_should_change_transaction_state_of_main_purchase_to_declined_if_failed_and_transaction_id_present()
    {
        $purchaseProcessedConverter = new PurchaseProcessedConverter();

        $result = $purchaseProcessedConverter->convert(
            [
                'version'                  => 3,
                'transaction_id'           => $this->faker->uuid,
                'status'                   => 'failed',
            ]
        );

        $this->assertEquals(Transaction::STATUS_DECLINED, $result['transaction_collection'][0]['state']);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\DomainEventConversionException
     */
    public function it_should_change_transaction_state_of_cross_sale_to_aborted_if_failed_and_no_transaction_id_present()
    {
        $purchaseProcessedConverter = new PurchaseProcessedConverter();

        $result = $purchaseProcessedConverter->convert(
            [
                'version'                  => 3,
                'transaction_id'           => '',
                'status'                   => 'failed',
                'cross_sale_purchase_data' => [
                    0 => [
                        'transactionId' => '',
                        'status'        => 'failed',
                        'addOnId'       => $this->faker->uuid,
                        'amount'        => $this->faker->randomFloat()
                    ]
                ]
            ]
        );

        $this->assertEquals(
            Transaction::STATUS_ABORTED,
            $result['cross_sale_purchase_data'][0]['transactionCollection'][0]['state']
        );
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\DomainEventConversionException
     */
    public function it_should_change_transaction_state_of_cross_sale_to_declined_if_failed_and_transaction_id_present()
    {
        $purchaseProcessedConverter = new PurchaseProcessedConverter();

        $result = $purchaseProcessedConverter->convert(
            [
                'version'                  => 3,
                'transaction_id'           => $this->faker->uuid,
                'status'                   => 'failed',
                'cross_sale_purchase_data' => [
                    0 => [
                        'transactionId' => $this->faker->uuid,
                        'status'        => 'failed',
                        'addOnId'       => $this->faker->uuid,
                        'amount'        => $this->faker->randomFloat()
                    ]
                ]
            ]
        );

        $this->assertEquals(
            Transaction::STATUS_DECLINED,
            $result['cross_sale_purchase_data'][0]['transactionCollection'][0]['state']
        );
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\DomainEventConversionException
     */
    public function it_should_add_item_id_equals_transaction_id_if_item_id_not_present_for_main_purchase()
    {
        $purchaseProcessedConverter = new PurchaseProcessedConverter();

        $result = $purchaseProcessedConverter->convert(
            [
                'version'                  => 3,
                'transaction_id'           => $this->faker->uuid,
                'status'                   => 'failed',
            ]
        );

        $this->assertEquals($result['transaction_collection'][0]['transactionId'], $result['item_id']);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\DomainEventConversionException
     */
    public function it_should_add_item_id_equals_transaction_id_if_item_id_not_present_for_cross_sale()
    {
        $purchaseProcessedConverter = new PurchaseProcessedConverter();

        $result = $purchaseProcessedConverter->convert(
            [
                'version'                  => 3,
                'transaction_id'           => $this->faker->uuid,
                'status'                   => 'failed',
                'cross_sale_purchase_data' => [
                    0 => [
                        'transactionId' => $this->faker->uuid,
                        'status'        => 'failed',
                        'addOnId'       => $this->faker->uuid,
                        'amount'        => $this->faker->randomFloat()
                    ]
                ]
            ]
        );

        $this->assertEquals(
            $result['cross_sale_purchase_data'][0]['transactionCollection'][0]['transactionId'],
            $result['cross_sale_purchase_data'][0]['itemId']
        );
    }
}
