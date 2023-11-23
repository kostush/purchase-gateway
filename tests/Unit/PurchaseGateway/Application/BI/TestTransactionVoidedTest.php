<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\BI;

use ProBillerNG\PurchaseGateway\Application\BI\TestTransactionVoided;
use Tests\UnitTestCase;

class TestTransactionVoidedTest extends UnitTestCase
{
    protected $eventDataKeys = [
        'type',
        'version',
        'timestamp',
        'transactionId',
        'testEvent',
        'amount',
        'ccCardFirst6',
        'ccCardLast4',
        'ccExpiration',
        'voidedSuccessfully',
    ];

    /**
     * @test
     * @return TestTransactionVoided
     * @throws \Exception
     */
    public function it_should_return_a_valid_test_transaction_voided_object()
    {
        $voidedTransaction = new TestTransactionVoided(
            $this->faker->uuid,
            1,
            10,
            '123456',
            '1234',
            '1024',
            1
        );

        $this->assertInstanceOf(TestTransactionVoided::class, $voidedTransaction);

        return $voidedTransaction;
    }

    /**
     * @test
     * @depends it_should_return_a_valid_test_transaction_voided_object
     * @param TestTransactionVoided $voidedTransaction TestTransactionVoided
     * @return void
     */
    public function it_should_contain_the_correct_event_keys($voidedTransaction)
    {
        $voidedTransactionEventData = $voidedTransaction->toArray();
        $success                    = true;

        foreach ($this->eventDataKeys as $key) {
            if (!array_key_exists($key, $voidedTransactionEventData)) {
                $success = false;
                break;
            }
        }

        $this->assertTrue($success);
    }
}
