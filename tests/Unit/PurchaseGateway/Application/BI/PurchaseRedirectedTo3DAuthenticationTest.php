<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\BI;

use ProBillerNG\PurchaseGateway\Application\BI\PurchaseRedirectedTo3DAuthentication;
use Tests\UnitTestCase;

class PurchaseRedirectedTo3DAuthenticationTest extends UnitTestCase
{
    protected $eventDataKeys = [
        'timestamp',
        'version',
        'sessionId',
        'status'
    ];

    /**
     * @test
     * @return PurchaseRedirectedTo3DAuthentication
     * @throws \Exception
     */
    public function it_should_return_a_valid_purchase_redirected_to_threed_auth_object()
    {
        $purchaseRedirected = new PurchaseRedirectedTo3DAuthentication(
            $this->faker->uuid,
            'pending'
        );

        $this->assertInstanceOf(PurchaseRedirectedTo3DAuthentication::class, $purchaseRedirected);

        return $purchaseRedirected;
    }

    /**
     * @test
     * @depends it_should_return_a_valid_purchase_redirected_to_threed_auth_object
     * @param PurchaseRedirectedTo3DAuthentication $purchaseRedirected PurchaseRedirectedTo3DAuthentication
     * @return void
     */
    public function it_should_contain_the_correct_event_keys($purchaseRedirected)
    {
        $purchaseRedirectedEventData = $purchaseRedirected->toArray();
        $success                       = true;

        foreach ($this->eventDataKeys as $key) {
            if (!array_key_exists($key, $purchaseRedirectedEventData)) {
                $success = false;
                break;
            }
        }

        $this->assertTrue($success);
    }
}
