<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

use ProBillerNG\PurchaseGateway\Domain\Model\Biller;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\BillerTransaction;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RocketgateBillerTransaction;
use Tests\UnitTestCase;

class RocketgateBillerTransactionTest extends UnitTestCase
{
    /**
     * @var string
     */
    private $invoiceId = '11111';

    /**
     * @var string
     */
    private $customerId = '22222';

    /**
     * @var string
     */
    private $billerTransactionId = '33333';

    /**
     * @var string
     */
    private $type = 'sale';

    /**
     * @test
     * @return Biller
     */
    public function it_should_return_a_biller_transaction_instance(): BillerTransaction
    {
        $billerTransaction = RocketgateBillerTransaction::create(
            $this->invoiceId,
            $this->customerId,
            $this->billerTransactionId,
            $this->type
        );

        $this->assertInstanceOf(BillerTransaction::class, $billerTransaction);

        return $billerTransaction;
    }

    /**
     * @test
     * @param BillerTransaction $billerTransaction The biller transaction object
     * @depends it_should_return_a_biller_transaction_instance
     * @return void
     */
    public function it_should_return_a_rocketgate_biller_transaction_object(BillerTransaction $billerTransaction): void
    {
        $this->assertInstanceOf(RocketgateBillerTransaction::class, $billerTransaction);
    }
}
