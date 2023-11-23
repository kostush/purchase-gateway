<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use Tests\UnitTestCase;

class TransactionCollectionTest extends UnitTestCase
{
    /**
     * @test
     * @throws \ReflectionException
     * @return void
     */
    public function is_valid_object_should_return_true_for_transaction(): void
    {
        $class  = new \ReflectionClass(TransactionCollection::class);
        $method = $class->getMethod('isValidObject');
        $method->setAccessible(true);
        $this->assertTrue(
            $method->invokeArgs(
                new TransactionCollection(),
                [
                    Transaction::create(
                        TransactionId::createFromString($this->faker->uuid),
                        'approved',
                        RocketgateBiller::BILLER_NAME
                    )
                ]
            )
        );
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function last_state_should_return_last_transaction_state(): void
    {
        $transactionCollection = new TransactionCollection(
            [
                Transaction::create(
                    TransactionId::createFromString($this->faker->uuid),
                    'declined',
                    RocketgateBiller::BILLER_NAME
                ),
                Transaction::create(
                    TransactionId::createFromString($this->faker->uuid),
                    'approved',
                    RocketgateBiller::BILLER_NAME
                )
            ]
        );

        $this->assertSame('approved', $transactionCollection->lastState());
    }
}
