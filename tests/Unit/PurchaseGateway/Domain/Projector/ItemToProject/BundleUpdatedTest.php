<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Projector\ItemToProject;

use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\BundleUpdated;
use Tests\UnitTestCase;

class BundleUpdatedTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_a_bundle_updated_from_received_event()
    {
        $data = [
            'id'          => 10,
            'eventBody'   => '{"aggregate_id":"a347f3c8-448e-424b-acca-039e6a9c5b69","occurred_on":"2019-10-10T13:12:06+00:00","bundle_id":"a347f3c8-448e-424b-acca-039e6a9c5b69","name":"Thad Gottlieb","require_active_content":true,"max_addon_number":4,"addons":["dd52bfdc-de8d-4522-adf1-3fb8b1a02a03"]}',
            'typeName'    => 'ProBillerNG\\BundleManagementAdmin\\Domain\\Model\\Event\\Bundle\\BundleUpdatedEvent',
            'aggregateId' => 'a347f3c8-448e-424b-acca-039e6a9c5b69',
            'occurredOn'  => '2019-10-10 13:12:06.000000',
        ];

        $bundleUpdated = new BundleUpdated(
            $data['id'],
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $data['occurredOn']),
            $data['eventBody']
        );
        $this->assertInstanceOf(
            BundleUpdated::class,
            $bundleUpdated
        );
    }
}
