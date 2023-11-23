<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Projector\ItemToProject;

use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\SiteUpdated;
use Tests\UnitTestCase;

class SiteUpdatedTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function it_should_return_site_updated_from_received_event()
    {
        $event = [
            'id'         => 3,
            'eventBody'  => '{"aggregate_id":"3ce1075f-6bd4-411a-b9c7-064a89c67ab9","occurred_on":"2019-11-15T16:16:41+00:00","site_id":"3ce1075f-6bd4-411a-b9c7-064a89c67ab9","name":"Brazzers","url":"http:\/\/www.brazzers.com","phone_number":"+1 777 7777","skype_number":"+1 777 8898","support_link":"","mail_support_link":"","message_support_link":"","postback_url":"url","isStickyGateway":"false","business_group_id":"9a0de60e-73bc-4454-b739-3677822aae2d","service_collection":[{"name":"a service","enabled":true}]}',
            'typeName'   => 'ProBillerNG\SiteAdmin\Domain\Model\Event\SiteUpdatedEvent',
            'agregateId' => '3ce1075f-6bd4-411a-b9c7-064a89c67ab9',
            'occurredOn' => '2019-11-15 16:16:42.0000'
        ];

        $siteUpdated = new SiteUpdated(
            $event['id'],
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $event['occurredOn']),
            $event['eventBody']
        );

        $this->assertInstanceOf(SiteUpdated::class, $siteUpdated);
    }
}
