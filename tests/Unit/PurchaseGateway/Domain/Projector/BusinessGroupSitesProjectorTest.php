<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Projector;

use ProBillerNG\Projection\Domain\Event\EventBuilder;
use ProBillerNG\PurchaseGateway\Domain\Projector\BusinessGroupSitesProjection;
use ProBillerNG\PurchaseGateway\Domain\Projector\BusinessGroupSitesProjector;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\BusinessGroupCreated;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\BusinessGroupDeleted;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\BusinessGroupUpdated;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\SiteCreated;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\SiteDeleted;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\SiteUpdated;
use ProBillerNG\PurchaseGateway\Domain\Projector\ProjectedItem\BusinessGroup;
use ProBillerNG\PurchaseGateway\Domain\Projector\ProjectedItem\BusinessGroupSite;
use Tests\UnitTestCase;

class BusinessGroupSitesProjectorTest extends UnitTestCase
{
    /** @var BusinessGroupSitesProjection */
    protected $projection;

    /** @var EventBuilder */
    protected $eventBuilder;

    /** @var BusinessGroupSitesProjector */
    protected $projector;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->projection   = $this->createMock(BusinessGroupSitesProjection::class);
        $this->eventBuilder = $this->createMock(EventBuilder::class);
        $this->projector    = new BusinessGroupSitesProjector($this->projection, $this->eventBuilder);
    }

    /**
     * @return BusinessGroupSite
     */
    private function businessGroupSiteCreate(): BusinessGroupSite
    {
        return BusinessGroupSite::create(
            $this->faker->uuid,
            $this->faker->uuid,
            'http://www.brazzers.com',
            'Brazzers',
            '1111-1111-1111',
            '2222-2222-2222',
            '',
            '',
            '',
            '',
            '',
            [
                'name'    => 'a service',
                'enabled' => true
            ],
            true,
            false
        );
    }

    /**
     * @return BusinessGroup
     */
    private function businessGroupCreate(): BusinessGroup
    {
        return BusinessGroup::create(
            $this->faker->uuid,
            'ab3708dc-1415-4654-9403-a4108999a80a',
            [
                'key'       => '3dcc4a19-e2a8-4622-8e03-52247bbd302d',
                'createdAt' => '2019-11-15T16:05:45+00:00'
            ],
            'Business group descriptor'
        );
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_true_if_business_group_created_event_is_subscribed(): void
    {
        $event = [
            'id'         => 1,
            'eventBody'  => '{"aggregate_id":"9a0de60e-73bc-4454-b739-3677822aae2d","occurred_on":"2019-11-15T16:11:41+00:00","business_group_id":"9a0de60e-73bc-4454-b739-3677822aae2d","name":"First business group","descriptor":"Business group descriptor","site_collection":[],"private_key":"ab3708dc-1415-4654-9403-a4108999a80a","public_key_collection":[{"key":"3dcc4a19-e2a8-4622-8e03-52247bbd302d","createdAt":"2019-11-15T16:05:45+00:00"}]}',
            'typeName'   => 'ProBillerNG\SiteAdmin\Domain\Model\Event\BusinessGroupCreatedEvent',
            'agregateId' => '9a0de60e-73bc-4454-b739-3677822aae2d',
            'occurredOn' => '2019-11-15 16:11:41.0000'
        ];

        $businessGroupCreated = new BusinessGroupCreated(
            $event['id'],
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $event['occurredOn']),
            $event['eventBody']
        );

        $this->assertTrue($this->projector->isSubscribedTo($businessGroupCreated));
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_true_if_business_group_updated_event_is_subscribed(): void
    {
        $event = [
            'id'         => 1,
            'eventBody'  => '{"aggregate_id":"9a0de60e-73bc-4454-b739-3677822aae2d","occurred_on":"2019-11-15T16:11:41+00:00","business_group_id":"9a0de60e-73bc-4454-b739-3677822aae2d","name":"First business group","descriptor":"Business group descriptor","site_collection":[],"private_key":"ab3708dc-1415-4654-9403-a4108999a80a","public_key_collection":[{"key":"3dcc4a19-e2a8-4622-8e03-52247bbd302d","createdAt":"2019-11-15T16:05:45+00:00"}]}',
            'typeName'   => 'ProBillerNG\SiteAdmin\Domain\Model\Event\BusinessGroupUpdatedEvent',
            'agregateId' => '9a0de60e-73bc-4454-b739-3677822aae2d',
            'occurredOn' => '2019-11-15 16:11:41.0000'
        ];

        $businessGroupUpdated = new BusinessGroupUpdated(
            $event['id'],
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $event['occurredOn']),
            $event['eventBody']
        );

        $this->assertTrue($this->projector->isSubscribedTo($businessGroupUpdated));
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_true_if_business_group_deleted_event_is_subscribed(): void
    {
        $event = [
            'id'         => 1,
            'eventBody'  => '{"aggregate_id":"9a0de60e-73bc-4454-b739-3677822aae2d","occurred_on":"2019-11-15T16:11:41+00:00","business_group_id":"9a0de60e-73bc-4454-b739-3677822aae2d","name":"First business group","descriptor":"Business group descriptor","site_collection":[],"private_key":"ab3708dc-1415-4654-9403-a4108999a80a","public_key_collection":[{"key":"3dcc4a19-e2a8-4622-8e03-52247bbd302d","createdAt":"2019-11-15T16:05:45+00:00"}]}',
            'typeName'   => 'ProBillerNG\SiteAdmin\Domain\Model\Event\BusinessGroupDeletedEvent',
            'agregateId' => '9a0de60e-73bc-4454-b739-3677822aae2d',
            'occurredOn' => '2019-11-15 16:11:41.0000'
        ];

        $businessGroupDeleted = new BusinessGroupDeleted(
            $event['id'],
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $event['occurredOn']),
            $event['eventBody']
        );

        $this->assertTrue($this->projector->isSubscribedTo($businessGroupDeleted));
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_true_if_site_created_event_is_subscribed(): void
    {
        $event = [
            'id'          => 2,
            'eventBody'   => '{"aggregate_id":"3ce1075f-6bd4-411a-b9c7-064a89c67ab9","occurred_on":"2019-11-15T16:12:41+00:00","site_id":"3ce1075f-6bd4-411a-b9c7-064a89c67ab9","name":"Brazzers","url":"http://www.brazzers.com","phone_number":"+1 777 8898","skype_number":"+1 777 8898","support_link":"","mail_support_link":"","message_support_link":"","postback_url":"url","isStickyGateway":false,"business_group_id":"9a0de60e-73bc-4454-b739-3677822aae2d","service_collection":[{"name":"a service","enabled":true}]}',
            'typeName'    => 'ProBillerNG\SiteAdmin\Domain\Model\Event\SiteCreatedEvent',
            'aggregateId' => '3ce1075f-6bd4-411a-b9c7-064a89c67ab9',
            'occurredOn'  => '2019-11-15 16:12:41.0000'
        ];

        $siteCreated = new SiteCreated(
            $event['id'],
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $event['occurredOn']),
            $event['eventBody']
        );

        $this->assertTrue($this->projector->isSubscribedTo($siteCreated));
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_true_if_site_updated_event_is_subscribed(): void
    {
        $event = [
            'id'          => 2,
            'eventBody'   => '{"aggregate_id":"3ce1075f-6bd4-411a-b9c7-064a89c67ab9","occurred_on":"2019-11-15T16:12:41+00:00","site_id":"3ce1075f-6bd4-411a-b9c7-064a89c67ab9","name":"Brazzers","url":"http://www.brazzers.com","phone_number":"+1 777 8898","skype_number":"+1 777 8898","support_link":"","mail_support_link":"","message_support_link":"","postback_url":"url","isStickyGateway":false,"business_group_id":"9a0de60e-73bc-4454-b739-3677822aae2d","service_collection":[{"name":"a service","enabled":true}]}',
            'typeName'    => 'ProBillerNG\SiteAdmin\Domain\Model\Event\SiteUpdatedEvent',
            'aggregateId' => '3ce1075f-6bd4-411a-b9c7-064a89c67ab9',
            'occurredOn'  => '2019-11-15 16:12:41.0000'
        ];

        $siteUpdated = new SiteUpdated(
            $event['id'],
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $event['occurredOn']),
            $event['eventBody']
        );

        $this->assertTrue($this->projector->isSubscribedTo($siteUpdated));
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_true_if_site_deleted_event_is_subscribed(): void
    {
        $event = [
            'id'          => 2,
            'eventBody'   => '{"aggregate_id":"3ce1075f-6bd4-411a-b9c7-064a89c67ab9","occurred_on":"2019-11-15T16:12:41+00:00","site_id":"3ce1075f-6bd4-411a-b9c7-064a89c67ab9","name":"Brazzers","url":"http://www.brazzers.com","phone_number":"+1 777 8898","skype_number":"+1 777 8898","support_link":"","mail_support_link":"","message_support_link":"","postback_url":"url","isStickyGateway":false,"business_group_id":"9a0de60e-73bc-4454-b739-3677822aae2d","service_collection":[{"name":"a service","enabled":true}]}',
            'typeName'    => 'ProBillerNG\SiteAdmin\Domain\Model\Event\SiteDeletedEvent',
            'aggregateId' => '3ce1075f-6bd4-411a-b9c7-064a89c67ab9',
            'occurredOn'  => '2019-11-15 16:12:41.0000'
        ];

        $siteDeleted = new SiteDeleted(
            $event['id'],
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $event['occurredOn']),
            $event['eventBody']
        );

        $this->assertTrue($this->projector->isSubscribedTo($siteDeleted));
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Projection\Domain\Exceptions\CannotRecreateEventException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_call_when_business_group_created(): void
    {
        $event = [
            'id'         => 1,
            'eventBody'  => '{"aggregate_id":"9a0de60e-73bc-4454-b739-3677822aae2d","occurred_on":"2019-11-15T16:05:45+00:00","business_group_id":"9a0de60e-73bc-4454-b739-3677822aae2d","name":"First business group","descriptor":"Business group descriptor","site_collection":[],"private_key":"ab3708dc-1415-4654-9403-a4108999a80a","public_key_collection":[{"key":"3dcc4a19-e2a8-4622-8e03-52247bbd302d","createdAt":"2019-11-15T16:05:45+00:00"}]}',
            'typeName'   => 'ProBillerNG\SiteAdmin\Domain\Model\Event\BusinessGroupCreatedEvent',
            'occurredOn' => '2019-11-15 16:11:41.0000'
        ];

        $businessGroupCreated = new BusinessGroupCreated(
            $event['id'],
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $event['occurredOn']),
            $event['eventBody']
        );

        $businessGroup = $this->businessGroupCreate();

        $this->eventBuilder->method('createFromItem')->willReturn($businessGroup);

        $this->projection->expects($this->once())->method('whenBusinessGroupCreated')->with($businessGroup);

        $this->projector->projectItem($businessGroupCreated);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Projection\Domain\Exceptions\CannotRecreateEventException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_call_when_business_group_updated(): void
    {
        $event = [
            'id'         => 5,
            'eventBody'  => '{"aggregate_id":"9a0de60e-73bc-4454-b739-3677822aae2d","occurred_on":"2019-11-15T16:29:52+00:00","business_group_id":"9a0de60e-73bc-4454-b739-3677822aae2d","name":"First business group","descriptor":"","private_key":"ab3708dc-1415-4654-9403-a4108999a80a","public_key_collection":[{"key":"3dcc4a19-e2a8-4622-8e03-52247bbd302d","createdAt":"2019-11-15T16:05:45+00:00"}]}',
            'typeName'   => 'ProBillerNG\SiteAdmin\Domain\Model\Event\BusinessGroupUpdatedEvent',
            'occurredOn' => '2019-11-15 16:29:52.0000'
        ];

        $businessGroupUpdated = new BusinessGroupUpdated(
            $event['id'],
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $event['occurredOn']),
            $event['eventBody']
        );

        $businessGroup = $this->businessGroupCreate();

        $this->eventBuilder->method('createFromItem')->willReturn($businessGroup);

        $this->projection->expects($this->once())->method('whenBusinessGroupUpdated')->with($businessGroup);

        $this->projector->projectItem($businessGroupUpdated);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Projection\Domain\Exceptions\CannotRecreateEventException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_call_when_business_group_deleted(): void
    {
        $event = [
            'id'         => 6,
            'eventBody'  => '{"aggregate_id":"9a0de60e-73bc-4454-b739-3677822aae2d","occurred_on":"2019-11-15T16:32:09+00:00","business_group_id":"9a0de60e-73bc-4454-b739-3677822aae2d"}',
            'typeName'   => 'ProBillerNG\SiteAdmin\Domain\Model\Event\BusinessGroupDeletedEvent',
            'occurredOn' => '2019-11-15 16:32:09.0000'
        ];

        $businessGroupDeleted = new BusinessGroupDeleted(
            $event['id'],
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $event['occurredOn']),
            $event['eventBody']
        );

        $businessGroup = $this->businessGroupCreate();

        $this->eventBuilder->method('createFromItem')->willReturn($businessGroup);

        $this->projection->expects($this->once())->method('whenBusinessGroupDeleted')->with($businessGroup);

        $this->projector->projectItem($businessGroupDeleted);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     * @throws \ProBillerNG\Projection\Domain\Exceptions\CannotRecreateEventException
     */
    public function it_should_call_when_site_created(): void
    {
        $event = [
            'id'          => 2,
            'eventBody'   => '{"aggregate_id":"3ce1075f-6bd4-411a-b9c7-064a89c67ab9","occurred_on":"2019-11-15T16:12:41+00:00","site_id":"3ce1075f-6bd4-411a-b9c7-064a89c67ab9","name":"Brazzers","url":"http://www.brazzers.com","phone_number":"+1 777 8898","skype_number":"+1 777 8898","support_link":"","mail_support_link":"","message_support_link":"","postback_url":"url","isStickyGateway":false,"business_group_id":"9a0de60e-73bc-4454-b739-3677822aae2d","service_collection":[{"name":"a service","enabled":true}]}',
            'typeName'    => 'ProBillerNG\SiteAdmin\Domain\Model\Event\SiteCreatedEvent',
            'aggregateId' => '3ce1075f-6bd4-411a-b9c7-064a89c67ab9',
            'occurredOn'  => '2019-11-15 16:12:41.0000'
        ];

        $siteCreated = new SiteCreated(
            $event['id'],
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $event['occurredOn']),
            $event['eventBody']
        );

        $businessGroupSite = $this->businessGroupSiteCreate();

        $this->eventBuilder->method('createFromItem')->willReturn($businessGroupSite);

        $this->projection->expects($this->once())->method('whenSiteCreated')->with($businessGroupSite);

        $this->projector->projectItem($siteCreated);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Projection\Domain\Exceptions\CannotRecreateEventException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_call_when_site_updated(): void
    {
        $event = [
            'id'          => 3,
            'aggregateId' => '3ce1075f-6bd4-411a-b9c7-064a89c67ab9',
            'eventBody'   => '{"aggregate_id":"3ce1075f-6bd4-411a-b9c7-064a89c67ab9","occurred_on":"2019-11-15T16:16:41+00:00","site_id":"3ce1075f-6bd4-411a-b9c7-064a89c67ab9","name":"Brazzers","url":"http:\/\/www.brazzers.com","phone_number":"+1 777 7777","skype_number":"+1 777 8898","support_link":"","mail_support_link":"","message_support_link":"","postback_url":"url","isStickyGateway":false,"business_group_id":"9a0de60e-73bc-4454-b739-3677822aae2d","service_collection":[{"name":"a service","enabled":true}]}',
            'typeName'    => 'ProBillerNG\SiteAdmin\Domain\Model\Event\SiteUpdatedEvent',
            'occurredOn'  => '2019-11-15 16:16:42.0000'
        ];

        $siteUpdated = new SiteUpdated(
            $event['id'],
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $event['occurredOn']),
            $event['eventBody']
        );

        $businessGroupSite = $this->businessGroupSiteCreate();

        $this->eventBuilder->method('createFromItem')->willReturn($businessGroupSite);

        $this->projection->expects($this->once())->method('whenSiteUpdated')->with($businessGroupSite);

        $this->projector->projectItem($siteUpdated);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Projection\Domain\Exceptions\CannotRecreateEventException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_call_when_site_deleted(): void
    {
        $event = [
            'id'         => 4,
            'eventBody'  => '{"aggregate_id":"3ce1075f-6bd4-411a-b9c7-064a89c67ab9","occurred_on":"2019-11-15T16:19:56+00:00","site_id":"3ce1075f-6bd4-411a-b9c7-064a89c67ab9"}',
            'typeName'   => 'ProBillerNG\SiteAdmin\Domain\Model\Event\SiteDeletedEvent',
            'occurredOn' => '2019-11-15 16:19:57.0000'
        ];

        $siteDeleted = new SiteDeleted(
            $event['id'],
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $event['occurredOn']),
            $event['eventBody']
        );

        $bussinessGroupSite = $this->businessGroupSiteCreate();

        $this->eventBuilder->method('createFromItem')->willReturn($bussinessGroupSite);

        $this->projection->expects($this->once())->method('whenSiteDeleted')->with($bussinessGroupSite);

        $this->projector->projectItem($siteDeleted);
    }
}
