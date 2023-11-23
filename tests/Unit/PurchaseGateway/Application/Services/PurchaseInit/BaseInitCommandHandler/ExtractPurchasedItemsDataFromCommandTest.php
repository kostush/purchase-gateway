<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\PurchaseInit\BaseInitCommandHandler;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\BaseInitCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\PurchaseInitCommand;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidAmountException;
use Tests\UnitTestCase;

class ExtractPurchasedItemsDataFromCommandTest extends UnitTestCase
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
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidAmountException
     * @throws \ReflectionException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->command = $this->createInitCommand();

        $this->handler = $this->createMock(BaseInitCommandHandler::class);

        $reflection = new \ReflectionClass(BaseInitCommandHandler::class);

        $method = $reflection->getMethod('extractPurchasedItemsDataFromCommand');
        $method->setAccessible(true);
        $this->method = $method;
    }

    /**
     * @test
     * @return array
     */
    public function it_should_create_main_purchase_data_array()
    {
        $extractedData = $this->method->invoke($this->handler, $this->command);

        $this->assertArrayHasKey('mainPurchase', $extractedData);

        return ['actual' => $extractedData['mainPurchase'], 'expected' => $this->command];
    }

    /**
     * @test
     * @depends it_should_create_main_purchase_data_array
     * @param array $testData Test data
     * @return void
     */
    public function main_purchase_data_should_have_correct_site_id(array $testData)
    {
        $actual = $testData['actual'];
        /** @var PurchaseInitCommand $expected */
        $expected = $testData['expected'];

        $this->assertEquals($expected->site()->siteId(), $actual['siteId']);
    }

    /**
     * @test
     * @depends it_should_create_main_purchase_data_array
     * @param array $testData Test data
     * @return void
     */
    public function main_purchase_data_should_have_correct_bundle_id(array $testData)
    {
        $actual = $testData['actual'];
        /** @var PurchaseInitCommand $expected */
        $expected = $testData['expected'];

        $this->assertEquals($expected->bundleId(), $actual['bundleId']);
    }

    /**
     * @test
     * @depends it_should_create_main_purchase_data_array
     * @param array $testData Test data
     * @return void
     */
    public function main_purchase_data_should_have_correct_addon_id(array $testData)
    {
        $actual = $testData['actual'];
        /** @var PurchaseInitCommand $expected */
        $expected = $testData['expected'];

        $this->assertEquals($expected->addOnId(), $actual['addonId']);
    }

    /**
     * @test
     * @depends it_should_create_main_purchase_data_array
     * @param array $testData Test data
     * @return void
     */
    public function main_purchase_data_should_have_correct_amount(array $testData)
    {
        $actual = $testData['actual'];
        /** @var PurchaseInitCommand $expected */
        $expected = $testData['expected'];

        $this->assertEquals($expected->amount(), $actual['amount']);
    }

    /**
     * @test
     * @depends it_should_create_main_purchase_data_array
     * @param array $testData Test data
     * @return void
     */
    public function main_purchase_data_should_have_correct_initial_days(array $testData)
    {
        $actual = $testData['actual'];
        /** @var PurchaseInitCommand $expected */
        $expected = $testData['expected'];

        $this->assertEquals($expected->initialDays(), $actual['initialDays']);
    }

    /**
     * @test
     * @depends it_should_create_main_purchase_data_array
     * @param array $testData Test data
     * @return void
     */
    public function main_purchase_data_should_have_correct_rebill_days(array $testData)
    {
        $actual = $testData['actual'];
        /** @var PurchaseInitCommand $expected */
        $expected = $testData['expected'];

        $this->assertEquals($expected->rebillDays(), $actual['rebillDays']);
    }

    /**
     * @test
     * @depends it_should_create_main_purchase_data_array
     * @param array $testData Test data
     * @return void
     */
    public function main_purchase_data_should_have_correct_rebill_amount(array $testData)
    {
        $actual = $testData['actual'];
        /** @var PurchaseInitCommand $expected */
        $expected = $testData['expected'];

        $this->assertEquals($expected->rebillAmount(), $actual['rebillAmount']);
    }

    /**
     * @test
     * @depends it_should_create_main_purchase_data_array
     * @param array $testData Test data
     * @return void
     */
    public function main_purchase_data_should_have_correct_is_trial(array $testData)
    {
        $actual = $testData['actual'];
        /** @var PurchaseInitCommand $expected */
        $expected = $testData['expected'];

        $this->assertEquals($expected->isTrial(), $actual['isTrial']);
    }

    /**
     * @test
     * @depends it_should_create_main_purchase_data_array
     * @param array $testData Test data
     * @return void
     */
    public function main_purchase_data_should_have_correct_tax(array $testData)
    {
        $actual = $testData['actual'];
        /** @var PurchaseInitCommand $expected */
        $expected = $testData['expected'];

        $this->assertEquals($expected->tax(), $actual['tax']);
    }

    /**
     * @test
     * @return array
     */
    public function it_should_create_cross_sales_data_array()
    {
        $extractedData = $this->method->invoke($this->handler, $this->command);

        $this->assertArrayHasKey('crossSales', $extractedData);

        return ['actual' => $extractedData['crossSales'][0], 'expected' => $this->command->crossSales()[0]];
    }

    /**
     * @test
     * @depends it_should_create_cross_sales_data_array
     * @param array $testData Test data
     * @return void
     */
    public function cross_sales_data_should_have_correct_site_id(array $testData)
    {
        $actual = $testData['actual'];
        /** @var PurchaseInitCommand $expected */
        $expected = $testData['expected'];

        $this->assertEquals($expected['siteId'], $actual['siteId']);
    }

    /**
     * @test
     * @depends it_should_create_cross_sales_data_array
     * @param array $testData Test data
     * @return void
     */
    public function cross_sales_data_should_have_correct_bundle_id(array $testData)
    {
        $actual = $testData['actual'];
        /** @var PurchaseInitCommand $expected */
        $expected = $testData['expected'];

        $this->assertEquals($expected['bundleId'], $actual['bundleId']);
    }

    /**
     * @test
     * @depends it_should_create_cross_sales_data_array
     * @param array $testData Test data
     * @return void
     */
    public function cross_sales_data_should_have_correct_addon_id(array $testData)
    {
        $actual = $testData['actual'];
        /** @var PurchaseInitCommand $expected */
        $expected = $testData['expected'];

        $this->assertEquals($expected['addonId'], $actual['addonId']);
    }

    /**
     * @test
     * @depends it_should_create_cross_sales_data_array
     * @param array $testData Test data
     * @return void
     */
    public function cross_sales_data_should_have_correct_amount(array $testData)
    {
        $actual = $testData['actual'];
        /** @var PurchaseInitCommand $expected */
        $expected = $testData['expected'];

        $this->assertEquals($expected['amount'], $actual['amount']);
    }

    /**
     * @test
     * @depends it_should_create_cross_sales_data_array
     * @param array $testData Test data
     * @return void
     */
    public function cross_sales_data_should_have_correct_initial_days(array $testData)
    {
        $actual = $testData['actual'];
        /** @var PurchaseInitCommand $expected */
        $expected = $testData['expected'];

        $this->assertEquals($expected['initialDays'], $actual['initialDays']);
    }

    /**
     * @test
     * @depends it_should_create_cross_sales_data_array
     * @param array $testData Test data
     * @return void
     */
    public function cross_sales_data_should_have_correct_rebill_days(array $testData)
    {
        $actual = $testData['actual'];
        /** @var PurchaseInitCommand $expected */
        $expected = $testData['expected'];

        $this->assertEquals($expected['rebillDays'], $actual['rebillDays']);
    }

    /**
     * @test
     * @depends it_should_create_cross_sales_data_array
     * @param array $testData Test data
     * @return void
     */
    public function cross_sales_data_should_have_correct_rebill_amount(array $testData)
    {
        $actual = $testData['actual'];
        /** @var PurchaseInitCommand $expected */
        $expected = $testData['expected'];

        $this->assertEquals($expected['rebillAmount'], $actual['rebillAmount']);
    }

    /**
     * @test
     * @depends it_should_create_cross_sales_data_array
     * @param array $testData Test data
     * @return void
     */
    public function cross_sales_data_should_have_correct_is_trial(array $testData)
    {
        $actual = $testData['actual'];
        /** @var PurchaseInitCommand $expected */
        $expected = $testData['expected'];

        $this->assertEquals($expected['isTrial'], $actual['isTrial']);
    }

    /**
     * @test
     * @depends it_should_create_cross_sales_data_array
     * @param array $testData Test data
     * @return void
     */
    public function cross_sales_data_should_have_correct_tax(array $testData)
    {
        $actual = $testData['actual'];
        /** @var PurchaseInitCommand $expected */
        $expected = $testData['expected'];

        $this->assertEquals($expected['tax'], $actual['tax']);
    }

    /**
     * @test
     * @return void
     * @throws InvalidAmountException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_create_a_cross_sales_data_entry_for_each_cross_sale()
    {
        $expectedCrossSales = 5;
        $crossSales         = $this->createCrossSaleArray(
            [0 => [], 1 => [], 2 => [], 3 => [], 4 => []]
        );

        $command = $this->createInitCommand(['crossSaleOptions' => $crossSales]);

        $extractedData = $this->method->invoke($this->handler, $command);

        $this->assertEquals($expectedCrossSales, count($extractedData['crossSales']));
    }
}
