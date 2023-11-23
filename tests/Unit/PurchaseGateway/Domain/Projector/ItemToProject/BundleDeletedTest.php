<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Projector\ItemToProject;

use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\BundleDeleted;
use Tests\UnitTestCase;

class BundleDeletedTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_a_bundle_delete_from_received_event()
    {
        $data = [
            'id'          => 9,
            'eventBody'   => '{"aggregate_id":"a347f3c8-448e-424b-acca-039e6a9c5b69","occurred_on":"2019-10-10T13:12:06+00:00","bundle_id":"a347f3c8-448e-424b-acca-039e6a9c5b69","name":"Mrs. Lorine Carroll","require_active_content":true,"max_addon_number":4,"addons":["dd52bfdc-de8d-4522-adf1-3fb8b1a02a03"]}',
            'typeName'    => 'ProBillerNG\\BundleManagementAdmin\\Domain\\Model\\Event\\Bundle\\BundleCreatedEvent',
            'aggregateId' => 'a347f3c8-448e-424b-acca-039e6a9c5b69',
            'occurredOn'  => '2019-10-10 13:12:06.000000',
        ];

        $bundleDeleted = new BundleDeleted(
            $data['id'],
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $data['occurredOn']),
            $data['eventBody']
        );
        $this->assertInstanceOf(
            BundleDeleted::class,
            $bundleDeleted
        );
    }
}
