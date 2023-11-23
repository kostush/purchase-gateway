<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\BI;

use ProBillerNG\PurchaseGateway\Application\BI\NuDataScoreRetrieved;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonId;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleId;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleRebillChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\Duration;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\Purchase;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Model\TaxInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use Tests\UnitTestCase;

class NuDataScoreRetrievedTest extends UnitTestCase
{
    /**
     * @var InitializedItem
     */
    private $initializedItem;

    /**
     * @var \ReflectionClass
     */
    private $nuDataScoreRetrievedReflection;

    /**
     * @var array
     */
    private $eventDataKeys = [
        'type',
        'version',
        'timestamp',
        'traceId',
        'correlationId',
        'sessionId',
        'purchaseId',
        'attemptedTransactions',
        'businessGroupId',
        'score'
    ];

    /**
     * @return void
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->nuDataScoreRetrievedReflection = new \ReflectionClass(NuDataScoreRetrieved::class);
        $chargeInformation                    = $this->createMock(BundleRebillChargeInformation::class);
        $chargeInformation->method('validFor')->willReturn(Duration::create(30));
        $taxInformation = $this->createMock(TaxInformation::class);

        $this->initializedItem = InitializedItem::create(
            SiteId::create(),
            BundleId::create(),
            AddonId::create(),
            $chargeInformation,
            $taxInformation,
            true,
            true,
            ''
        );

        $transaction = Transaction::create(
            TransactionId::createFromString('74a47dad-66b6-4760-8772-0fbf1da28deb'),
            'approved',
            RocketgateBiller::BILLER_NAME,
            null
        );

        $transactionCollection = new TransactionCollection();
        $transactionCollection->add($transaction);
        $this->initializedItem = $this->createMock(InitializedItem::class);
        $this->initializedItem->method('transactionCollection')->willReturn($transactionCollection);
    }

    /**
     * @test
     * @return NuDataScoreRetrieved
     * @throws \Exception
     */
    public function it_should_return_a_valid_nu_data_score_retrieved_object(): NuDataScoreRetrieved
    {
        $nuDataScoreRetrieved = new NuDataScoreRetrieved(
            $this->faker->uuid,
            $this->faker->uuid,
            $this->faker->uuid,
            $this->createMock(Purchase::class),
            $this->initializedItem,
            [$this->initializedItem],
            $this->faker->uuid,
            '{"rinfo":{"rid":"rc-N2U0MDNkNzdkYjYzYWNmODJmMTVmYzVkYTE2OWE0MWYx"}}'
        );

        $this->assertInstanceOf(NuDataScoreRetrieved::class, $nuDataScoreRetrieved);

        return $nuDataScoreRetrieved;
    }

    /**
     * @test
     * @depends it_should_return_a_valid_nu_data_score_retrieved_object
     * @param NuDataScoreRetrieved $nuDataScoreRetrieved NuData Score Retrieved
     * @return void
     */
    public function it_should_contain_the_correct_event_keys($nuDataScoreRetrieved): void
    {
        $nuDataScoreRetrievedData = $nuDataScoreRetrieved->toArray();
        $success                  = true;

        foreach ($this->eventDataKeys as $key) {
            if (!array_key_exists($key, $nuDataScoreRetrievedData)) {
                $success = false;
                break;
            }
        }

        $this->assertTrue($success);
    }

    /**
     * @test
     * @depends it_should_return_a_valid_nu_data_score_retrieved_object
     * @param NuDataScoreRetrieved $nuDataScoreRetrieved NuData Score Retrieved
     * @return void
     * @throws \ReflectionException
     */
    public function get_item_attempted_transactions_should_return_an_array($nuDataScoreRetrieved): void
    {
        $method = $this->nuDataScoreRetrievedReflection->getMethod('getItemAttemptedTransactions');
        $method->setAccessible(true);

        $this->assertIsArray(
            $method->invokeArgs(
                $nuDataScoreRetrieved,
                [$this->initializedItem]
            )
        );
    }

    /**
     * @test
     * @depends it_should_return_a_valid_nu_data_score_retrieved_object
     * @param NuDataScoreRetrieved $nuDataScoreRetrieved NuData Score Retrieved
     * @return void
     * @throws \ReflectionException
     */
    public function get_item_attempted_transactions_should_return_an_array_with_correct_number_of_items(
        $nuDataScoreRetrieved
    ): void {
        $method = $this->nuDataScoreRetrievedReflection->getMethod('getItemAttemptedTransactions');
        $method->setAccessible(true);

        $this->assertEquals(
            1,
            count(
                $method->invokeArgs(
                    $nuDataScoreRetrieved,
                    [$this->initializedItem]
                )
            )
        );
    }

    /**
     * @test
     * @depends it_should_return_a_valid_nu_data_score_retrieved_object
     * @param NuDataScoreRetrieved $nuDataScoreRetrieved NuData Score Retrieved
     * @return void
     * @throws \ReflectionException
     */
    public function get_cross_sales_attempted_transactions_should_return_an_array($nuDataScoreRetrieved): void
    {
        $method = $this->nuDataScoreRetrievedReflection->getMethod('getCrossSalesAttemptedTransactions');
        $method->setAccessible(true);

        $this->assertIsArray(
            $method->invokeArgs(
                $nuDataScoreRetrieved,
                [[$this->initializedItem]]
            )
        );
    }

    /**
     * @test
     * @depends it_should_return_a_valid_nu_data_score_retrieved_object
     * @param NuDataScoreRetrieved $nuDataScoreRetrieved NuData Score Retrieved
     * @return void
     * @throws \ReflectionException
     */
    public function get_cross_sales_transactions_should_return_an_array_with_correct_number_of_items(
        $nuDataScoreRetrieved
    ): void {
        $method = $this->nuDataScoreRetrievedReflection->getMethod('getCrossSalesAttemptedTransactions');
        $method->setAccessible(true);

        $this->assertEquals(
            1,
            count(
                $method->invokeArgs(
                    $nuDataScoreRetrieved,
                    [[$this->initializedItem]]
                )
            )
        );
    }

    /**
     * @test
     * @depends it_should_return_a_valid_nu_data_score_retrieved_object
     * @param NuDataScoreRetrieved $nuDataScoreRetrieved NuData Score Retrieved
     * @return void
     * @throws \ReflectionException
     */
    public function get_cross_sales_transactions_should_return_an_array_with_array_items(
        $nuDataScoreRetrieved
    ): void {
        $method = $this->nuDataScoreRetrievedReflection->getMethod('getCrossSalesAttemptedTransactions');
        $method->setAccessible(true);

        $this->assertIsArray(
            $method->invokeArgs(
                $nuDataScoreRetrieved,
                [[$this->initializedItem]]
            )[0]
        );
    }

    /**
     * @test
     * @depends it_should_return_a_valid_nu_data_score_retrieved_object
     * @param NuDataScoreRetrieved $nuDataScoreRetrieved NuData Score Retrieved
     * @return void
     * @throws \ReflectionException
     */
    public function get_attempted_transactions_should_return_an_array_with_the_correct_keys($nuDataScoreRetrieved): void
    {
        $method = $this->nuDataScoreRetrievedReflection->getMethod('getAttemptedTransactions');
        $method->setAccessible(true);
        $success = true;

        foreach (['mainItem', 'crossSaleItems'] as $key) {
            if (!array_key_exists(
                $key,
                $method->invokeArgs($nuDataScoreRetrieved, [])
            )
            ) {
                $success = false;
                break;
            }
        }

        $this->assertTrue($success);
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function prepare_score_should_return_given_string_if_not_json()
    {
        $method = $this->nuDataScoreRetrievedReflection->getMethod('prepareScore');
        $method->setAccessible(true);

        $score = 'test string';
        $preparedScore = $method->invoke($this->createMock(NuDataScoreRetrieved::class), $score);

        $this->assertEquals($score, $preparedScore);
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function prepare_score_should_return_array_if_json()
    {
        $method = $this->nuDataScoreRetrievedReflection->getMethod('prepareScore');
        $method->setAccessible(true);

        $score = '{"test": "score"}';
        $preparedScore = $method->invoke($this->createMock(NuDataScoreRetrieved::class), $score);

        $this->assertTrue(is_array($preparedScore));
    }
}
