<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\BI;

use ProBillerNG\PurchaseGateway\Application\BI\Purchase3DSLookup;
use Tests\UnitTestCase;

class Purchase3DSLookupTest extends UnitTestCase
{
    protected $eventDataKeys = [
        'timestamp',
        'version',
        'sessionId',
        'threedVersion'
    ];

    /**
     * @test
     * @return Purchase3DSLookup
     * @throws \Exception
     */
    public function it_should_return_a_valid_purchase_threeds_lookup_object()
    {
        $purchaseLookup = new Purchase3DSLookup(
            $this->faker->uuid,
            2
        );

        $this->assertInstanceOf(Purchase3DSLookup::class, $purchaseLookup);

        return $purchaseLookup;
    }

    /**
     * @test
     * @depends it_should_return_a_valid_purchase_threeds_lookup_object
     * @param Purchase3DSLookup $purchaseLookup Purchase3DSLookup
     * @return void
     */
    public function it_should_contain_the_correct_event_keys($purchaseLookup)
    {
        $purchaseLookupEventData = $purchaseLookup->toArray();
        $success                       = true;

        foreach ($this->eventDataKeys as $key) {
            if (!array_key_exists($key, $purchaseLookupEventData)) {
                $success = false;
                break;
            }
        }

        $this->assertTrue($success);
    }
}
