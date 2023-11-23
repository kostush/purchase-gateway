<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Projector\ItemToProject;

use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\AddonDeleted;
use Tests\UnitTestCase;

class AddonDeletedTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_an_addon_deleted_from_received_event()
    {
        $data = [
            'id'          => 3,
            'eventBody'   => '{"aggregate_id":"79968893-33d8-4f9f-b218-8fe7e5c884ce","occurred_on":"2019-10-10T13:12:00+00:00","addon_id":"79968893-33d8-4f9f-b218-8fe7e5c884ce"}',
            'typeName'    => 'ProBillerNG\\BundleManagementAdmin\\Domain\\Model\\Event\\Addon\\AddonDeletedEvent',
            'aggregateId' => '79968893-33d8-4f9f-b218-8fe7e5c884ce',
            'occurredOn'  => '2019-10-10 13:12:00.000000',
        ];

        $addonDeleted = new AddonDeleted(
            $data['id'],
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $data['occurredOn']),
            $data['eventBody']
        );
        $this->assertInstanceOf(
            AddonDeleted::class,
            $addonDeleted
        );
    }
}
