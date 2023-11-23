<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Projector;

use ProBillerNG\Projection\Domain\Event\EventBuilder;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonType;
use ProBillerNG\PurchaseGateway\Domain\Projector\BundleAddonsProjection;
use ProBillerNG\PurchaseGateway\Domain\Projector\BundleAddonsProjector;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\AddonCreated;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\AddonDeleted;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\AddonUpdated;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\BundleCreated;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\BundleDeleted;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\BundleUpdated;
use ProBillerNG\PurchaseGateway\Domain\Projector\ProjectedItem\Addon;
use ProBillerNG\PurchaseGateway\Domain\Projector\ProjectedItem\BundleAddon;
use Tests\UnitTestCase;

class BundleAddonsProjectorTest extends UnitTestCase
{
    /** @var BundleAddonsProjection */
    protected $projection;

    /** @var EventBuilder */
    protected $eventBuilder;

    /**
     * @var BundleAddonsProjector
     */
    protected $projector;

    /**
     * @throws \ReflectionException
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->projection   = $this->createMock(BundleAddonsProjection::class);
        $this->eventBuilder = $this->createMock(EventBuilder::class);
        $this->projector    = new BundleAddonsProjector($this->projection, $this->eventBuilder);
    }

    /**
     * @test
     * @return void
     * @throws \Throwable
     */
    public function it_should_return_true_if_addon_created_event_is_subscribed(): void
    {
        $data = [
            'id'          => 1,
            'eventBody'   => '{"aggregate_id":"b81c6b98-a828-4e6d-b439-434cbb9fd39a","occurred_on":"2019-10-10T13:11:58+00:00","addon_id":"b81c6b98-a828-4e6d-b439-434cbb9fd39a","name":"Kip Gorczany V","type":"quis"}',
            'typeName'    => 'ProBillerNG\\BundleManagementAdmin\\Domain\\Model\\Event\\Addon\\AddonCreatedEvent',
            'aggregateId' => 'b81c6b98-a828-4e6d-b439-434cbb9fd39a',
            'occurredOn'  => '2019-10-10 13:11:58.000000',
        ];

        $addonCreated = new AddonCreated(
            $data['id'],
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $data['occurredOn']),
            $data['eventBody']
        );

        $this->assertTrue($this->projector->isSubscribedTo($addonCreated));
    }

    /**
     * @test
     * @return void
     * @throws \Throwable
     */
    public function it_should_return_true_if_addon_updated_event_is_subscribed(): void
    {
        $data = [
            'id'          => 1,
            'eventBody'   => '{"aggregate_id":"b81c6b98-a828-4e6d-b439-434cbb9fd39a","occurred_on":"2019-10-10T13:11:58+00:00","addon_id":"b81c6b98-a828-4e6d-b439-434cbb9fd39a","name":"Kip Gorczany V","type":"quis"}',
            'typeName'    => 'ProBillerNG\\BundleManagementAdmin\\Domain\\Model\\Event\\Addon\\AddonUpdatedEvent',
            'aggregateId' => 'b81c6b98-a828-4e6d-b439-434cbb9fd39a',
            'occurredOn'  => '2019-10-10 13:11:58.000000',
        ];

        $addonUpdated = new AddonUpdated(
            $data['id'],
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $data['occurredOn']),
            $data['eventBody']
        );

        $this->assertTrue($this->projector->isSubscribedTo($addonUpdated));
    }

    /**
     * @test
     * @return void
     * @throws \Throwable
     */
    public function it_should_return_true_if_addon_deleted_event_is_subscribed(): void
    {
        $data = [
            'id'          => 1,
            'eventBody'   => '{"aggregate_id":"b81c6b98-a828-4e6d-b439-434cbb9fd39a","occurred_on":"2019-10-10T13:11:58+00:00","addon_id":"b81c6b98-a828-4e6d-b439-434cbb9fd39a","name":"Kip Gorczany V","type":"quis"}',
            'typeName'    => 'ProBillerNG\\BundleManagementAdmin\\Domain\\Model\\Event\\Addon\\AddonDeletedEvent',
            'aggregateId' => 'b81c6b98-a828-4e6d-b439-434cbb9fd39a',
            'occurredOn'  => '2019-10-10 13:11:58.000000',
        ];

        $addonDeleted = new AddonDeleted(
            $data['id'],
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $data['occurredOn']),
            $data['eventBody']
        );

        $this->assertTrue($this->projector->isSubscribedTo($addonDeleted));
    }

    /**
     * @test
     * @return void
     * @throws \Throwable
     */
    public function it_should_return_true_if_bundle_created_event_is_subscribed(): void
    {
        $data = [
            'id'          => 1,
            'eventBody'   => '{"aggregate_id":"7b400504-861a-4fb1-a2d3-4f86961fc323","occurred_on":"2019-10-10T13:14:03+00:00","bundle_id":"7b400504-861a-4fb1-a2d3-4f86961fc323","name":"Amanda Kertzmann","require_active_content":true,"max_addon_number":1,"addons":["b81c6b98-a828-4e6d-b439-434cbb9fd39a"]}',
            'typeName'    => 'ProBillerNG\\BundleManagementAdmin\\Domain\\Model\\Event\\Bundle\\BundleCreatedEvent',
            'aggregateId' => '7b400504-861a-4fb1-a2d3-4f86961fc323',
            'occurredOn'  => '2019-10-10 13:14:04.000000',
        ];

        $bundleCreated = new BundleCreated(
            $data['id'],
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $data['occurredOn']),
            $data['eventBody']
        );

        $this->assertTrue($this->projector->isSubscribedTo($bundleCreated));
    }

    /**
     * @test
     * @return void
     * @throws \Throwable
     */
    public function it_should_return_true_if_bundle_updated_event_is_subscribed(): void
    {
        $data = [
            'id'          => 1,
            'eventBody'   => '{"aggregate_id":"7b400504-861a-4fb1-a2d3-4f86961fc323","occurred_on":"2019-10-10T13:14:03+00:00","bundle_id":"7b400504-861a-4fb1-a2d3-4f86961fc323","name":"Amanda Kertzmann","require_active_content":true,"max_addon_number":1,"addons":["b81c6b98-a828-4e6d-b439-434cbb9fd39a"]}',
            'typeName'    => 'ProBillerNG\\BundleManagementAdmin\\Domain\\Model\\Event\\Bundle\\BundleUpdatedEvent',
            'aggregateId' => '7b400504-861a-4fb1-a2d3-4f86961fc323',
            'occurredOn'  => '2019-10-10 13:14:04.000000',
        ];

        $bundleUpdated = new BundleUpdated(
            $data['id'],
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $data['occurredOn']),
            $data['eventBody']
        );

        $this->assertTrue($this->projector->isSubscribedTo($bundleUpdated));
    }

    /**
     * @test
     * @return void
     * @throws \Throwable
     */
    public function it_should_return_true_if_bundle_deleted_event_is_subscribed(): void
    {
        $data = [
            'id'          => 1,
            'eventBody'   => '{"aggregate_id":"7b400504-861a-4fb1-a2d3-4f86961fc323","occurred_on":"2019-10-10T13:14:03+00:00","bundle_id":"7b400504-861a-4fb1-a2d3-4f86961fc323","name":"Amanda Kertzmann","require_active_content":true,"max_addon_number":1,"addons":["b81c6b98-a828-4e6d-b439-434cbb9fd39a"]}',
            'typeName'    => 'ProBillerNG\\BundleManagementAdmin\\Domain\\Model\\Event\\Bundle\\BundleDeletedEvent',
            'aggregateId' => '7b400504-861a-4fb1-a2d3-4f86961fc323',
            'occurredOn'  => '2019-10-10 13:14:04.000000',
        ];

        $bundleDeleted = new BundleDeleted(
            $data['id'],
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $data['occurredOn']),
            $data['eventBody']
        );

        $this->assertTrue($this->projector->isSubscribedTo($bundleDeleted));
    }

    /**
     * @test
     * @return void
     * @throws \Throwable
     */
    public function it_should_call_when_addon_created(): void
    {
        $data = [
            'id'          => 1,
            'eventBody'   => '{"aggregate_id":"b81c6b98-a828-4e6d-b439-434cbb9fd39a","occurred_on":"2019-10-10T13:11:58+00:00","addon_id":"b81c6b98-a828-4e6d-b439-434cbb9fd39a","name":"Kip Gorczany V","type":"quis"}',
            'typeName'    => 'ProBillerNG\\BundleManagementAdmin\\Domain\\Model\\Event\\Addon\\AddonCreatedEvent',
            'aggregateId' => 'b81c6b98-a828-4e6d-b439-434cbb9fd39a',
            'occurredOn'  => '2019-10-10 13:11:58.000000',
        ];

        $addonCreated = new AddonCreated(
            $data['id'],
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $data['occurredOn']),
            $data['eventBody']
        );

        $addon = new Addon($this->faker->uuid, AddonType::CONTENT);

        $this->eventBuilder->method('createFromItem')->willReturn($addon);

        $this->projection->expects($this->once())->method('whenAddonCreated')->with($addon);

        $this->projector->projectItem($addonCreated);
    }

    /**
     * @test
     * @return void
     * @throws \Throwable
     */
    public function it_should_call_when_addon_updated(): void
    {
        $data = [
            'id'          => 1,
            'eventBody'   => '{"aggregate_id":"b81c6b98-a828-4e6d-b439-434cbb9fd39a","occurred_on":"2019-10-10T13:11:58+00:00","addon_id":"b81c6b98-a828-4e6d-b439-434cbb9fd39a","name":"Kip Gorczany V","type":"quis"}',
            'typeName'    => 'ProBillerNG\\BundleManagementAdmin\\Domain\\Model\\Event\\Addon\\AddonUpdatedEvent',
            'aggregateId' => 'b81c6b98-a828-4e6d-b439-434cbb9fd39a',
            'occurredOn'  => '2019-10-10 13:11:58.000000',
        ];

        $addonUpdated = new AddonUpdated(
            $data['id'],
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $data['occurredOn']),
            $data['eventBody']
        );

        $addon = new Addon($this->faker->uuid, AddonType::CONTENT);

        $this->eventBuilder->method('createFromItem')->willReturn($addon);
        $this->projection->expects($this->once())->method('whenAddonUpdated')->with($addon);
        $this->projector->projectItem($addonUpdated);
    }

    /**
     * @test
     * @return void
     * @throws \Throwable
     */
    public function it_should_call_when_addon_deleted(): void
    {
        $data = [
            'id'          => 1,
            'eventBody'   => '{"aggregate_id":"b81c6b98-a828-4e6d-b439-434cbb9fd39a","occurred_on":"2019-10-10T13:11:58+00:00","addon_id":"b81c6b98-a828-4e6d-b439-434cbb9fd39a","name":"Kip Gorczany V","type":"quis"}',
            'typeName'    => 'ProBillerNG\\BundleManagementAdmin\\Domain\\Model\\Event\\Addon\\AddonUpdatedEvent',
            'aggregateId' => 'b81c6b98-a828-4e6d-b439-434cbb9fd39a',
            'occurredOn'  => '2019-10-10 13:11:58.000000',
        ];

        $addonDeleted = new AddonDeleted(
            $data['id'],
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $data['occurredOn']),
            $data['eventBody']
        );

        $addon = new Addon($this->faker->uuid, null);

        $this->eventBuilder->method('createFromItem')->willReturn($addon);
        $this->projection->expects($this->once())->method('whenAddonDeleted')->with($addon);
        $this->projector->projectItem($addonDeleted);
    }

    /**
     * @test
     * @return void
     * @throws \Throwable
     */
    public function it_should_call_when_bundle_created(): void
    {
        $data = [
            'id'          => 9,
            'eventBody'   => '{"aggregate_id":"a347f3c8-448e-424b-acca-039e6a9c5b69","occurred_on":"2019-10-10T13:12:06+00:00","bundle_id":"a347f3c8-448e-424b-acca-039e6a9c5b69","name":"Mrs. Lorine Carroll","require_active_content":true,"max_addon_number":4,"addons":["dd52bfdc-de8d-4522-adf1-3fb8b1a02a03"]}',
            'typeName'    => 'ProBillerNG\\BundleManagementAdmin\\Domain\\Model\\Event\\Bundle\\BundleCreatedEvent',
            'aggregateId' => 'a347f3c8-448e-424b-acca-039e6a9c5b69',
            'occurredOn'  => '2019-10-10 13:12:06.000000',
        ];

        $bundleCreated = new BundleCreated(
            $data['id'],
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $data['occurredOn']),
            $data['eventBody']
        );

        $bundle = new BundleAddon($this->faker->uuid, true, [$this->faker->uuid]);

        $this->eventBuilder->method('createFromItem')->willReturn($bundle);

        $this->projection->expects($this->once())->method('whenBundleCreated')->with($bundle);

        $this->projector->projectItem($bundleCreated);
    }

    /**
     * @test
     * @return void
     * @throws \Throwable
     */
    public function it_should_call_when_bundle_updated(): void
    {
        $data = [
            'id'          => 9,
            'eventBody'   => '{"aggregate_id":"a347f3c8-448e-424b-acca-039e6a9c5b69","occurred_on":"2019-10-10T13:12:06+00:00","bundle_id":"a347f3c8-448e-424b-acca-039e6a9c5b69","name":"Mrs. Lorine Carroll","require_active_content":true,"max_addon_number":4,"addons":["dd52bfdc-de8d-4522-adf1-3fb8b1a02a03"]}',
            'typeName'    => 'ProBillerNG\\BundleManagementAdmin\\Domain\\Model\\Event\\Bundle\\BundleUpdatedEvent',
            'aggregateId' => 'a347f3c8-448e-424b-acca-039e6a9c5b69',
            'occurredOn'  => '2019-10-10 13:12:06.000000',
        ];

        $bundleUpdated = new BundleUpdated(
            $data['id'],
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $data['occurredOn']),
            $data['eventBody']
        );

        $bundle = new BundleAddon($this->faker->uuid, true, [$this->faker->uuid]);

        $this->eventBuilder->method('createFromItem')->willReturn($bundle);

        $this->projection->expects($this->once())->method('whenBundleUpdated')->with($bundle);

        $this->projector->projectItem($bundleUpdated);
    }

    /**
     * @test
     * @return void
     * @throws \Throwable
     */
    public function it_should_call_when_bundle_deleted(): void
    {
        $data = [
            'id'          => 8,
            'eventBody'   => '{"aggregate_id":"b2e6da65-e8a0-4e94-8886-4c63ef75bd23","occurred_on":"2019-10-10T13:12:04+00:00","bundle_id":"b2e6da65-e8a0-4e94-8886-4c63ef75bd23"}',
            'typeName'    => 'ProBillerNG\\BundleManagementAdmin\\Domain\\Model\\Event\\Bundle\\BundleDeletedEvent',
            'aggregateId' => 'b2e6da65-e8a0-4e94-8886-4c63ef75bd23',
            'occurredOn'  => '2019-10-10 13:12:04.000000',
        ];

        $bundleDeleted = new BundleDeleted(
            $data['id'],
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $data['occurredOn']),
            $data['eventBody']
        );

        $bundle = new BundleAddon($this->faker->uuid, null, null);

        $this->eventBuilder->method('createFromItem')->willReturn($bundle);

        $this->projection->expects($this->once())->method('whenBundleDeleted')->with($bundle);

        $this->projector->projectItem($bundleDeleted);
    }
}