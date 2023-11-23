<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Domain;

use ProBillerNG\PurchaseGateway\Domain\EventTracker;
use ProBillerNG\PurchaseGateway\Domain\EventTrackerId;
use Tests\UnitTestCase;

class EventTrackerTest extends UnitTestCase
{
    /**
     * @test
     * @return EventTracker
     * @throws \Exception
     */
    public function it_should_return_an_event_tracker_object_if_correct_data_is_sent()
    {
        $eventTracker = new EventTracker(
            EventTrackerId::create(),
            EventTracker::PURCHASE_DOMAIN_EVENT_TYPE,
            new \DateTimeImmutable(),
            new \DateTimeImmutable('25.04.2019'),
            new \DateTimeImmutable()
        );
        $this->assertInstanceOf(EventTracker::class, $eventTracker);
        return $eventTracker;
    }

    /**
     * @test
     * @param EventTracker $eventTracker EventTracker
     * @depends it_should_return_an_event_tracker_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_an_event_tracker_id(EventTracker $eventTracker)
    {
        $this->assertInstanceOf(EventTrackerId::class, $eventTracker->eventTrackerId());
    }

    /**
     * @test
     * @param EventTracker $eventTracker EventTracker
     * @depends it_should_return_an_event_tracker_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_an_event_tracker_type(EventTracker $eventTracker)
    {
        $this->assertEquals(EventTracker::PURCHASE_DOMAIN_EVENT_TYPE, $eventTracker->eventTrackerType());
    }

    /**
     * @test
     * @param EventTracker $eventTracker EventTracker
     * @depends it_should_return_an_event_tracker_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_a_last_processed_event_date(EventTracker $eventTracker)
    {
        $this->assertInstanceOf(\DateTimeImmutable::class, $eventTracker->lastProcessedEventDate());
    }

    /**
     * @test
     * @param EventTracker $eventTracker EventTracker
     * @depends it_should_return_an_event_tracker_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_a_created_on_date(EventTracker $eventTracker)
    {
        $this->assertInstanceOf(\DateTimeImmutable::class, $eventTracker->createdOn());
    }

    /**
     * @test
     * @param EventTracker $eventTracker EventTracker
     * @depends it_should_return_an_event_tracker_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_an_updated_on_date(EventTracker $eventTracker)
    {
        $this->assertInstanceOf(\DateTimeImmutable::class, $eventTracker->updatedOn());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_tracker_types_when_get_event_tracker_types_is_called()
    {
        $this->assertIsArray(EventTracker::getEventTrackerTypes());
    }

    /**
     * @test
     * @param EventTracker $eventTracker EventTracker
     * @depends it_should_return_an_event_tracker_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_return_true_if_event_tracker_type_exists(EventTracker $eventTracker)
    {
        $this->assertEquals(EventTracker::PURCHASE_DOMAIN_EVENT_TYPE, $eventTracker->eventTrackerType());
    }
}
