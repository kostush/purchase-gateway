<?php

namespace Tests\Unit\PurchaseGateway\Domain\Projector\ItemSource;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemSource\BusinessGroupSiteRetriever;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\BusinessGroupCreated;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\BusinessGroupDeleted;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\BusinessGroupUpdated;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\SiteCreated;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\SiteDeleted;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\SiteUpdated;
use ProBillerNG\PurchaseGateway\Domain\Services\SiteAdminService;
use Tests\UnitTestCase;

class BusinessGroupSiteRetrieverTest extends UnitTestCase
{
    /**
     * @var MockObject|SiteAdminService
     */
    private $siteAdminService;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->siteAdminService = $this->createMock(SiteAdminService::class);

        $events = [
            [
                'id'         => 1,
                'eventBody'  => '{"aggregate_id":"9a0de60e-73bc-4454-b739-3677822aae2d","occurred_on":"2019-11-15T16:11:41+00:00","business_group_id":"9a0de60e-73bc-4454-b739-3677822aae2d","name":"First business group","descriptor":"Business group descriptor","site_collection":[],"private_key":"ab3708dc-1415-4654-9403-a4108999a80a","public_key_collection":[{"key":"3dcc4a19-e2a8-4622-8e03-52247bbd302d","createdAt":"2019-11-15T16:05:45+00:00"}]}',
                'typeName'   => 'ProBillerNG\SiteAdmin\Domain\Model\Event\BusinessGroupCreatedEvent',
                'agregateId' => '9a0de60e-73bc-4454-b739-3677822aae2d',
                'occurredOn' => '2019-11-15 16:11:41.0000'
            ],
            [
                'id'         => 2,
                'eventBody'  => '{"aggregate_id":"9a0de60e-73bc-4454-b739-3677822aae2d","occurred_on":"2019-11-15T16:29:52+00:00","business_group_id":"9a0de60e-73bc-4454-b739-3677822aae2d","name":"First business group","descriptor":"","private_key":"ab3708dc-1415-4654-9403-a4108999a80a","public_key_collection":[{"key":"3dcc4a19-e2a8-4622-8e03-52247bbd302d","createdAt":"2019-11-15T16:05:45+00:00"}]}',
                'typeName'   => 'ProBillerNG\SiteAdmin\Domain\Model\Event\BusinessGroupUpdatedEvent',
                'agregateId' => '9a0de60e-73bc-4454-b739-3677822aae2d',
                'occurredOn' => '2019-11-15 16:29:52.0000'
            ],
            [
                'id'         => 3,
                'eventBody'  => '{"aggregate_id":"9a0de60e-73bc-4454-b739-3677822aae2d","occurred_on":"2019-11-15T16:32:09+00:00","business_group_id":"9a0de60e-73bc-4454-b739-3677822aae2d"}',
                'typeName'   => 'ProBillerNG\SiteAdmin\Domain\Model\Event\BusinessGroupDeletedEvent',
                'agregateId' => '9a0de60e-73bc-4454-b739-3677822aae2d',
                'occurredOn' => '2019-11-15 16:32:09.0000'
            ],
            [
                'id'         => 4,
                'eventBody'  => '{"aggregate_id":"3ce1075f-6bd4-411a-b9c7-064a89c67ab9","occurred_on":"2019-11-15T16:12:41+00:00","site_id":"3ce1075f-6bd4-411a-b9c7-064a89c67ab9","name":"Brazzers","url":"http://www.brazzers.com","phone_number":"+1 777 8898","skype_number":"+1 777 8898","support_link":"","mail_support_link":"","message_support_link":"","postback_url":"url","isStickyGateway":false,"business_group_id":"9a0de60e-73bc-4454-b739-3677822aae2d","service_collection":[{"name":"a service","enabled":true}]}',
                'typeName'   => 'ProBillerNG\SiteAdmin\Domain\Model\Event\SiteCreatedEvent',
                'agregateId' => '3ce1075f-6bd4-411a-b9c7-064a89c67ab9',
                'occurredOn' => '2019-11-15 16:12:41.0000'
            ],
            [
                'id'         => 5,
                'eventBody'  => '{"aggregate_id":"3ce1075f-6bd4-411a-b9c7-064a89c67ab9","occurred_on":"2019-11-15T16:16:41+00:00","site_id":"3ce1075f-6bd4-411a-b9c7-064a89c67ab9","name":"Brazzers","url":"http:\/\/www.brazzers.com","phone_number":"+1 777 7777","skype_number":"+1 777 8898","support_link":"","mail_support_link":"","message_support_link":"","postback_url":"url","isStickyGateway":false,"business_group_id":"9a0de60e-73bc-4454-b739-3677822aae2d","service_collection":[{"name":"a service","enabled":true}]}',
                'typeName'   => 'ProBillerNG\SiteAdmin\Domain\Model\Event\SiteUpdatedEvent',
                'agregateId' => '3ce1075f-6bd4-411a-b9c7-064a89c67ab9',
                'occurredOn' => '2019-11-15 16:16:42.0000'
            ],
            [
                'id'         => 6,
                'eventBody'  => '{"aggregate_id":"3ce1075f-6bd4-411a-b9c7-064a89c67ab9","occurred_on":"2019-11-15T16:19:56+00:00","site_id":"3ce1075f-6bd4-411a-b9c7-064a89c67ab9"}',
                'typeName'   => 'ProBillerNG\SiteAdmin\Domain\Model\Event\SiteDeletedEvent',
                'agregateId' => '3ce1075f-6bd4-411a-b9c7-064a89c67ab9',
                'occurredOn' => '2019-11-15 16:19:57.0000'
            ],
            [
                'id'         => 7,
                'eventBody'  => '{"aggregate_id":"7bfc2154-6a00-4910-ba87-dad0793207bd","occurred_on":"2019-11-15T16:21:56+00:00"}',
                'typeName'   => 'ProBillerNG\SiteAdmin\Domain\Model\Event\SomeOtherEvent',
                'agregateId' => '7bfc2154-6a00-4910-ba87-dad0793207bd',
                'occurredOn' => '2019-11-15 16:21:57.0000'
            ]
        ];

        $this->siteAdminService->method('retrieveEvents')->willReturn($events);
    }

    /**
     * @test
     * @return array
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_six_types_of_events_for_sites_when_next_batch_of_items_since_is_called(): array
    {
        $businessGroupSitesRetriever = new BusinessGroupSiteRetriever($this->siteAdminService);

        $itemsToProject = $businessGroupSitesRetriever->nextBatchOfItemsSince(1, 10);

        $this->assertCount(6, $itemsToProject);

        return $itemsToProject;
    }

    /**
     * @test
     * @depends it_should_return_six_types_of_events_for_sites_when_next_batch_of_items_since_is_called
     * @param array $itemsToProject Items to project
     * @return void
     */
    public function it_should_have_a_business_group_created_event_as_the_first_item_in_the_array(
        array $itemsToProject
    ): void {
        $this->assertInstanceOf(BusinessGroupCreated::class, $itemsToProject[0]);
    }

    /**
     * @test
     * @depends it_should_return_six_types_of_events_for_sites_when_next_batch_of_items_since_is_called
     * @param array $itemsToProject Items to project
     * @return void
     */
    public function it_should_have_a_business_group_updated_event_as_the_second_item_in_the_array(
        array $itemsToProject
    ): void {
        $this->assertInstanceOf(BusinessGroupUpdated::class, $itemsToProject[1]);
    }

    /**
     * @test
     * @depends it_should_return_six_types_of_events_for_sites_when_next_batch_of_items_since_is_called
     * @param array $itemsToProject Items to project
     * @return void
     */
    public function it_should_have_a_business_group_deleted_event_as_the_third_item_in_the_array(
        array $itemsToProject
    ): void {
        $this->assertInstanceOf(BusinessGroupDeleted::class, $itemsToProject[2]);
    }

    /**
     * @test
     * @depends it_should_return_six_types_of_events_for_sites_when_next_batch_of_items_since_is_called
     * @param array $itemsToProject Items to project
     * @return void
     */
    public function it_should_have_a_site_created_event_as_the_fourth_item_in_the_array(
        array $itemsToProject
    ): void {
        $this->assertInstanceOf(SiteCreated::class, $itemsToProject[3]);
    }

    /**
     * @test
     * @depends it_should_return_six_types_of_events_for_sites_when_next_batch_of_items_since_is_called
     * @param array $itemsToProject Items to project
     * @return void
     */
    public function it_should_have_a_site_updated_event_as_the_fifth_item_in_the_array(
        array $itemsToProject
    ): void {
        $this->assertInstanceOf(SiteUpdated::class, $itemsToProject[4]);
    }

    /**
     * @test
     * @depends it_should_return_six_types_of_events_for_sites_when_next_batch_of_items_since_is_called
     * @param array $itemsToProject Items to project
     * @return void
     */
    public function it_should_have_a_site_deleted_event_as_the_sixth_item_in_the_array(
        array $itemsToProject
    ): void {
        $this->assertInstanceOf(SiteDeleted::class, $itemsToProject[5]);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_empty_array_if_given_event_is_not_correct(): void
    {
        $siteAdminService = $this->createMock(SiteAdminService::class);

        $event = [
            'id'          => 7,
            'eventBody'   => '{"aggregate_id":"7b400504-861a-4fb1-a2d3-4f86961fc323","occurred_on":"2019-10-10T13:16:06+00:00","event_id":"7b400504-861a-4fb1-a2d3-4f86961fc323"}',
            'typeName'    => 'ProBillerNG\\BundleManagementAdmin\\Domain\\Model\\Event\\Bundle\\SomeOtherEvent',
            'aggregateId' => '7b400504-861a-4fb1-a2d3-4f86961fc323',
            'occurredOn'  => '2019-10-10 13:16:06.000000',
        ];

        $businessGroupSitesRetriever = new BusinessGroupSiteRetriever($siteAdminService);

        $siteAdminService->method('retrieveEvents')->willReturn([$event]);

        $itemsToProject = $businessGroupSitesRetriever->nextBatchOfItemsSince(1, 10);

        $this->assertEmpty($itemsToProject);
    }
}
