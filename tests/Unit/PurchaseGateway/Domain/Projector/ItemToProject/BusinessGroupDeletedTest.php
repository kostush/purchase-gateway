<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Projector\ItemToProject;

use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\BusinessGroupDeleted;
use Tests\UnitTestCase;

class BusinessGroupDeletedTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function it_should_return_business_group_deleted_from_received_event()
    {
        $event = [
            'id'         => 6,
            'eventBody'  => '{"aggregate_id":"9a0de60e-73bc-4454-b739-3677822aae2d","occurred_on":"2019-11-15T16:32:09+00:00","business_group_id":"9a0de60e-73bc-4454-b739-3677822aae2d"}',
            'typeName'   => 'ProBillerNG\SiteAdmin\Domain\Model\Event\BusinessGroupDeletedEvent',
            'agregateId' => '9a0de60e-73bc-4454-b739-3677822aae2d',
            'occurredOn' => '2019-11-15 16:32:09.0000'
        ];

        $businessGroupDeleted = new BusinessGroupDeleted(
            $event['id'],
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $event['occurredOn']),
            $event['eventBody']
        );

        $this->assertInstanceOf(BusinessGroupDeleted::class, $businessGroupDeleted);
    }
}
