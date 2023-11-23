<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Projector\ItemToProject;

use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\BusinessGroupCreated;
use Tests\UnitTestCase;

class BusinessGroupCreatedTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function it_should_return_business_group_created_from_received_event()
    {
        $event = [
            'id'         => 1,
            'eventBody'  => '{"aggregate_id":"9a0de60e-73bc-4454-b739-3677822aae2d","occurred_on":"2019-11-15T16:05:45+00:00","business_group_id":"9a0de60e-73bc-4454-b739-3677822aae2d","name":"First business group","descriptor":"Business group descriptor","site_collection":[],"private_key":"ab3708dc-1415-4654-9403-a4108999a80a","public_key_collection":[{"key":"3dcc4a19-e2a8-4622-8e03-52247bbd302d","createdAt":"2019-11-15T16:05:45+00:00"}]}',
            'typeName'   => 'ProBillerNG\SiteAdmin\Domain\Model\Event\BusinessGroupCreatedEvent',
            'agregateId' => '9a0de60e-73bc-4454-b739-3677822aae2d',
            'occurredOn' => '2019-11-15 16:11:41.0000'
        ];

        $businessGroupCreated = new BusinessGroupCreated(
            $event['id'],
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $event['occurredOn']),
            $event['eventBody']
        );

        $this->assertInstanceOf(BusinessGroupCreated::class, $businessGroupCreated);
    }
}
