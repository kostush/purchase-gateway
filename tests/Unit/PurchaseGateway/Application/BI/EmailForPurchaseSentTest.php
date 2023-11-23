<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\BI;

use ProBillerNG\PurchaseGateway\Application\BI\EmailForPurchaseSent;
use ProBillerNG\PurchaseGateway\Application\BI\Processed\Member;
use Tests\UnitTestCase;

class EmailForPurchaseSentTest extends UnitTestCase
{
    protected $eventDataKeys = [
        'timestamp',
        'sessionId',
        'type',
        'version',
        'memberInfo',
        'memberId',
        'subscriptionId',
        'purchaseId',
        'receiptId'
    ];

    /**
     * @test
     * @return EmailForPurchaseSent
     * @throws \Exception
     */
    public function it_should_return_a_valid_email_for_purchase_sent_object()
    {
        $memberInfo = $this->createMock(Member::class);

        $purchaseProcessed = new EmailForPurchaseSent(
            $memberInfo,
            $this->faker->uuid,
            $this->faker->uuid,
            $this->faker->uuid,
            $this->faker->uuid,
            $this->faker->uuid,
            $this->faker->uuid
        );

        $this->assertInstanceOf(EmailForPurchaseSent::class, $purchaseProcessed);

        return $purchaseProcessed;
    }

    /**
     * @test
     * @depends it_should_return_a_valid_email_for_purchase_sent_object
     * @param EmailForPurchaseSent $emailForPurchaseSent EmailForPurchaseSent
     * @return void
     */
    public function it_should_contain_the_correct_event_keys($emailForPurchaseSent)
    {
        $emailForPurchaseSentEventData = $emailForPurchaseSent->toArray();
        $success                       = true;

        foreach ($this->eventDataKeys as $key) {
            if (!array_key_exists($key, $emailForPurchaseSentEventData)) {
                $success = false;
                break;
            }
        }

        $this->assertTrue($success);
    }
}
