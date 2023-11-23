<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Projector\ItemToProject;

use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\AddonUpdated;
use Tests\UnitTestCase;

class AddonUpdatedTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_an_addon_updated_from_received_event()
    {
        $data = [
            'id'          => 5,
            'eventBody'   => '{"aggregate_id":"dd52bfdc-de8d-4522-adf1-3fb8b1a02a03","occurred_on":"2019-10-10T13:12:02+00:00","addon_id":"dd52bfdc-de8d-4522-adf1-3fb8b1a02a03","name":"Kyler Bradtke","type":"feature"}',
            'typeName'    => 'ProBillerNG\\BundleManagementAdmin\\Domain\\Model\\Event\\Addon\\AddonUpdatedEvent',
            'aggregateId' => 'dd52bfdc-de8d-4522-adf1-3fb8b1a02a03',
            'occurredOn'  => '2019-10-10 13:12:03.000000',
        ];

        $addonUpdated = new AddonUpdated(
            $data['id'],
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $data['occurredOn']),
            $data['eventBody']
        );
        $this->assertInstanceOf(
            AddonUpdated::class,
            $addonUpdated
        );
    }
}
