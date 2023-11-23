<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Projector\ItemToProject;

use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\BundleCreated;
use Tests\UnitTestCase;

class BundleCreateTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_a_bundle_created_from_received_event()
    {
        $data = [
            'id'          => 6,
            'eventBody'   => '{"aggregate_id":"7b400504-861a-4fb1-a2d3-4f86961fc323","occurred_on":"2019-10-10T13:12:03+00:00","bundle_id":"7b400504-861a-4fb1-a2d3-4f86961fc323","name":"Amanda Kertzmann","require_active_content":true,"max_addon_number":1,"addons":["dd52bfdc-de8d-4522-adf1-3fb8b1a02a03"]}',
            'typeName'    => 'ProBillerNG\\BundleManagementAdmin\\Domain\\Model\\Event\\Bundle\\BundleCreatedEvent',
            'aggregateId' => '7b400504-861a-4fb1-a2d3-4f86961fc323',
            'occurredOn'  => '2019-10-10 13:12:04.000000',
        ];

        $bundleCreated = new BundleCreated(
            $data['id'],
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $data['occurredOn']),
            $data['eventBody']
        );
        $this->assertInstanceOf(
            BundleCreated::class,
            $bundleCreated
        );
    }
}
