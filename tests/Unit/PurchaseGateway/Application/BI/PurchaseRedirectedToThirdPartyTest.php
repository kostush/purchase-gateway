<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\BI;

use ProBillerNG\PurchaseGateway\Application\BI\PurchaseRedirectedToThirdParty;
use Tests\UnitTestCase;

class PurchaseRedirectedToThirdPartyTest extends UnitTestCase
{
    protected $eventDataKeys = [
        'timestamp',
        'version',
        'sessionId',
        'status'
    ];

    /**
     * @test
     * @return PurchaseRedirectedToThirdParty
     * @throws \Exception
     */
    public function it_should_return_a_valid_purchase_redirected_to_third_party_object(): PurchaseRedirectedToThirdParty
    {
        $purchaseRedirected = new PurchaseRedirectedToThirdParty(
            $this->faker->uuid,
            'pending'
        );

        $this->assertInstanceOf(PurchaseRedirectedToThirdParty::class, $purchaseRedirected);

        return $purchaseRedirected;
    }

    /**
     * @test
     * @depends it_should_return_a_valid_purchase_redirected_to_third_party_object
     * @param PurchaseRedirectedToThirdParty $purchaseRedirected PurchaseRedirectedToThirdParty
     * @return void
     */
    public function it_should_contain_the_correct_event_keys($purchaseRedirected): void
    {
        $purchaseRedirectedEventData = $purchaseRedirected->toArray();
        $success                     = true;

        foreach ($this->eventDataKeys as $key) {
            if (!array_key_exists($key, $purchaseRedirectedEventData)) {
                $success = false;
                break;
            }
        }

        $this->assertTrue($success);
    }
}
