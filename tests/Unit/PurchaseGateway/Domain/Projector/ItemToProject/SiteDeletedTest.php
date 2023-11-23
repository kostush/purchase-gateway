<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Projector\ItemToProject;

use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\SiteDeleted;
use Tests\UnitTestCase;

class SiteDeletedTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function it_should_return_site_deleted_from_received_event()
    {
        $event = [
            'id'         => 4,
            'eventBody'  => '{"aggregate_id":"3ce1075f-6bd4-411a-b9c7-064a89c67ab9","occurred_on":"2019-11-15T16:19:56+00:00","site_id":"3ce1075f-6bd4-411a-b9c7-064a89c67ab9"}',
            'typeName'   => 'ProBillerNG\SiteAdmin\Domain\Model\Event\SiteDeletedEvent',
            'agregateId' => '3ce1075f-6bd4-411a-b9c7-064a89c67ab9',
            'occurredOn' => '2019-11-15 16:19:57.0000'
        ];

        $siteDeleted = new SiteDeleted(
            $event['id'],
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $event['occurredOn']),
            $event['eventBody']
        );

        $this->assertInstanceOf(SiteDeleted::class, $siteDeleted);
    }
}
