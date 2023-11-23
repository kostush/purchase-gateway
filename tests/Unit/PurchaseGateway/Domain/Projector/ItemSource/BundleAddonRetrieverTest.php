<?php

namespace Tests\Unit\PurchaseGateway\Domain\Projector\ItemSource;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemSource\BundleAddonRetriever;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\AddonCreated;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\AddonDeleted;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\AddonUpdated;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\BundleCreated;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\BundleDeleted;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\BundleUpdated;
use ProBillerNG\PurchaseGateway\Domain\Services\BundleManagementAdminService;
use Tests\UnitTestCase;

class BundleAddonRetrieverTest extends UnitTestCase
{
    /**
     * @var MockObject|BundleManagementAdminService
     */
    private $bundleManagementAdminService;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->bundleManagementAdminService = $this->createMock(BundleManagementAdminService::class);

        $events = [
            [
                'id'          => 1,
                'eventBody'   => '{"aggregate_id":"b81c6b98-a828-4e6d-b439-434cbb9fd39a","occurred_on":"2019-10-10T13:11:58+00:00","addon_id":"b81c6b98-a828-4e6d-b439-434cbb9fd39a","name":"Kip Gorczany V","type":"quis"}',
                'typeName'    => 'ProBillerNG\\BundleManagementAdmin\\Domain\\Model\\Event\\Addon\\AddonCreatedEvent',
                'aggregateId' => 'b81c6b98-a828-4e6d-b439-434cbb9fd39a',
                'occurredOn'  => '2019-10-10 13:11:58.000000',
            ],
            [
                'id'          => 2,
                'eventBody'   => '{"aggregate_id":"b81c6b98-a828-4e6d-b439-434cbb9fd39a","occurred_on":"2019-10-10T13:12:02+00:00","addon_id":"b81c6b98-a828-4e6d-b439-434cbb9fd39a","name":"Kyler Bradtke","type":"feature"}',
                'typeName'    => 'ProBillerNG\\BundleManagementAdmin\\Domain\\Model\\Event\\Addon\\AddonUpdatedEvent',
                'aggregateId' => 'b81c6b98-a828-4e6d-b439-434cbb9fd39a',
                'occurredOn'  => '2019-10-10 13:12:03.000000',
            ],
            [
                'id'          => 3,
                'eventBody'   => '{"aggregate_id":"b81c6b98-a828-4e6d-b439-434cbb9fd39a","occurred_on":"2019-10-10T13:13:00+00:00","addon_id":"b81c6b98-a828-4e6d-b439-434cbb9fd39a"}',
                'typeName'    => 'ProBillerNG\\BundleManagementAdmin\\Domain\\Model\\Event\\Addon\\AddonDeletedEvent',
                'aggregateId' => '79968893-33d8-4f9f-b218-8fe7e5c884ce',
                'occurredOn'  => '2019-10-10 13:13:00.000000',
            ],
            [
                'id'          => 4,
                'eventBody'   => '{"aggregate_id":"7b400504-861a-4fb1-a2d3-4f86961fc323","occurred_on":"2019-10-10T13:14:03+00:00","bundle_id":"7b400504-861a-4fb1-a2d3-4f86961fc323","name":"Amanda Kertzmann","require_active_content":true,"max_addon_number":1,"addons":["b81c6b98-a828-4e6d-b439-434cbb9fd39a"]}',
                'typeName'    => 'ProBillerNG\\BundleManagementAdmin\\Domain\\Model\\Event\\Bundle\\BundleCreatedEvent',
                'aggregateId' => '7b400504-861a-4fb1-a2d3-4f86961fc323',
                'occurredOn'  => '2019-10-10 13:14:04.000000',
            ],
            [
                'id'          => 5,
                'eventBody'   => '{"aggregate_id":"7b400504-861a-4fb1-a2d3-4f86961fc323","occurred_on":"2019-10-10T13:15:06+00:00","bundle_id":"7b400504-861a-4fb1-a2d3-4f86961fc323","name":"Thad Gottlieb","require_active_content":true,"max_addon_number":4,"addons":["b81c6b98-a828-4e6d-b439-434cbb9fd39a"]}',
                'typeName'    => 'ProBillerNG\\BundleManagementAdmin\\Domain\\Model\\Event\\Bundle\\BundleUpdatedEvent',
                'aggregateId' => '7b400504-861a-4fb1-a2d3-4f86961fc323',
                'occurredOn'  => '2019-10-10 13:15:06.000000',
            ],
            [
                'id'          => 6,
                'eventBody'   => '{"aggregate_id":"7b400504-861a-4fb1-a2d3-4f86961fc323","occurred_on":"2019-10-10T13:16:06+00:00","bundle_id":"7b400504-861a-4fb1-a2d3-4f86961fc323","name":"Mrs. Lorine Carroll","require_active_content":true,"max_addon_number":4,"addons":["b81c6b98-a828-4e6d-b439-434cbb9fd39a"]}',
                'typeName'    => 'ProBillerNG\\BundleManagementAdmin\\Domain\\Model\\Event\\Bundle\\BundleDeletedEvent',
                'aggregateId' => '7b400504-861a-4fb1-a2d3-4f86961fc323',
                'occurredOn'  => '2019-10-10 13:16:06.000000',
            ]
        ];

        $this->bundleManagementAdminService->method('retrieveEvents')->willReturn($events);
    }

    /**
     * @test
     * @return array
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_six_types_of_events_for_bundles_when_next_batch_of_items_since_is_called(): array
    {
        $bundleAddonRetriever = new BundleAddonRetriever($this->bundleManagementAdminService);

        $itemsToProject = $bundleAddonRetriever->nextBatchOfItemsSince(1, 10);

        $this->assertCount(6, $itemsToProject);

        return $itemsToProject;
    }

    /**
     * @test
     * @depends it_should_return_six_types_of_events_for_bundles_when_next_batch_of_items_since_is_called
     * @param array $itemsToProject Items to project
     * @return void
     */
    public function it_should_have_a_addon_created_event_as_the_first_item_in_the_array(
        array $itemsToProject
    ): void {
        $this->assertInstanceOf(AddonCreated::class, $itemsToProject[0]);
    }

    /**
     * @test
     * @depends it_should_return_six_types_of_events_for_bundles_when_next_batch_of_items_since_is_called
     * @param array $itemsToProject Items to project
     * @return void
     */
    public function it_should_have_a_addon_updated_event_as_the_second_item_in_the_array(
        array $itemsToProject
    ): void {
        $this->assertInstanceOf(AddonUpdated::class, $itemsToProject[1]);
    }

    /**
     * @test
     * @depends it_should_return_six_types_of_events_for_bundles_when_next_batch_of_items_since_is_called
     * @param array $itemsToProject Items to project
     * @return void
     */
    public function it_should_have_a_addon_deleted_event_as_the_third_item_in_the_array(
        array $itemsToProject
    ): void {
        $this->assertInstanceOf(AddonDeleted::class, $itemsToProject[2]);
    }

    /**
     * @test
     * @depends it_should_return_six_types_of_events_for_bundles_when_next_batch_of_items_since_is_called
     * @param array $itemsToProject Items to project
     * @return void
     */
    public function it_should_have_a_bundle_created_event_as_the_fourth_item_in_the_array(
        array $itemsToProject
    ): void {
        $this->assertInstanceOf(BundleCreated::class, $itemsToProject[3]);
    }

    /**
     * @test
     * @depends it_should_return_six_types_of_events_for_bundles_when_next_batch_of_items_since_is_called
     * @param array $itemsToProject Items to project
     * @return void
     */
    public function it_should_have_a_bundle_updated_event_as_the_fifth_item_in_the_array(
        array $itemsToProject
    ): void {
        $this->assertInstanceOf(BundleUpdated::class, $itemsToProject[4]);
    }

    /**
     * @test
     * @depends it_should_return_six_types_of_events_for_bundles_when_next_batch_of_items_since_is_called
     * @param array $itemsToProject Items to project
     * @return void
     */
    public function it_should_have_a_bundle_deleted_event_as_the_sixth_item_in_the_array(
        array $itemsToProject
    ): void {
        $this->assertInstanceOf(BundleDeleted::class, $itemsToProject[5]);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_empty_array_if_given_event_is_not_correct(): void
    {
        $bundleManagementAdminService = $this->createMock(BundleManagementAdminService::class);

        $event = [
            'id'          => 7,
            'eventBody'   => '{"aggregate_id":"7b400504-861a-4fb1-a2d3-4f86961fc323","occurred_on":"2019-10-10T13:16:06+00:00","event_id":"7b400504-861a-4fb1-a2d3-4f86961fc323"}',
            'typeName'    => 'ProBillerNG\\BundleManagementAdmin\\Domain\\Model\\Event\\Bundle\\SomeOtherEvent',
            'aggregateId' => '7b400504-861a-4fb1-a2d3-4f86961fc323',
            'occurredOn'  => '2019-10-10 13:16:06.000000',
        ];

        $bundleAddonRetriever = new BundleAddonRetriever($bundleManagementAdminService);

        $bundleManagementAdminService->method('retrieveEvents')->willReturn([$event]);

        $itemsToProject = $bundleAddonRetriever->nextBatchOfItemsSince(1, 10);

        $this->assertEmpty($itemsToProject);
    }
}
