<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Services\Mgpg;

use ProbillerMGPG\SubsequentOperations\Init\Operation\SubscriptionExpiredRenew;
use ProbillerMGPG\SubsequentOperations\Init\Operation\SubscriptionTrialUpgrade;
use ProbillerMGPG\SubsequentOperations\Init\Operation\SubscriptionUpgrade;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\BusinessTransactionOperationNotFoundException;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\InvalidBusinessTransactionOperationException;
use ProBillerNG\PurchaseGateway\Domain\Services\Mgpg\SubsequentOperationFactory;
use Tests\UnitTestCase;

class SubsequentOperationFactoryTest extends UnitTestCase
{
    /**
     * @test
     * @throws BusinessTransactionOperationNotFoundException
     * @throws InvalidBusinessTransactionOperationException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_subscription_trial_upgrade_class(): void
    {
        $class = SubsequentOperationFactory::createSubsequentOperation('subscriptionTrialUpgrade');
        $this->assertEquals(SubscriptionTrialUpgrade::class, $class);
    }

    /**
     * @test
     * @throws BusinessTransactionOperationNotFoundException
     * @throws InvalidBusinessTransactionOperationException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_subscription_expired_renew_class(): void
    {
        $class = SubsequentOperationFactory::createSubsequentOperation('subscriptionExpiredRenew');
        $this->assertEquals(SubscriptionExpiredRenew::class, $class);
    }

    /**
     * @test
     * @throws BusinessTransactionOperationNotFoundException
     * @throws InvalidBusinessTransactionOperationException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_subscription_upgrade(): void
    {
        $class = SubsequentOperationFactory::createSubsequentOperation('subscriptionUpgrade');
        $this->assertEquals(SubscriptionUpgrade::class, $class);
    }

    /**
     * @throws \ProBillerNG\Logger\Exception
     * @throws BusinessTransactionOperationNotFoundException
     * @throws InvalidBusinessTransactionOperationException
     */
    public function it_should_return_exception_when_business_transaction_is_invalid_for_subsequent_operation(): void
    {
        $this->expectException(InvalidBusinessTransactionOperationException::class);
        SubsequentOperationFactory::createSubsequentOperation('singleChargePurchase');
    }

    /**
     * @test
     * @throws BusinessTransactionOperationNotFoundException
     * @throws InvalidBusinessTransactionOperationException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_exception_when_business_transaction_is_unknown(): void
    {
        $this->expectException(BusinessTransactionOperationNotFoundException::class);
        SubsequentOperationFactory::createSubsequentOperation('unknownOperation');
    }
}
