<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Domain;

use ProBillerNG\PurchaseGateway\Domain\FailedEventPublish;
use Tests\UnitTestCase;

class FailedEventPublishTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_create_an_entity_when_aggregate_id_provided()
    {
        $failedEventPublish = FailedEventPublish::create($this->faker->uuid);

        $this->assertInstanceOf(FailedEventPublish::class, $failedEventPublish);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_be_created_with_published_false()
    {
        $failedEventPublish = FailedEventPublish::create($this->faker->uuid);

        $this->assertFalse($failedEventPublish->published());
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_be_created_with_retries_zero()
    {
        $failedEventPublish = FailedEventPublish::create($this->faker->uuid);

        $this->assertEquals(0, $failedEventPublish->retries());
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function increase_retry_count_should_increase_retries_by_one()
    {
        $failedEventPublish = FailedEventPublish::create($this->faker->uuid);

        $failedEventPublish->increaseRetryCount();

        $this->assertEquals(1, $failedEventPublish->retries());
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function increase_retry_count_should_update_last_attempted()
    {
        $failedEventPublish = FailedEventPublish::create($this->faker->uuid);

        $initialDate = $failedEventPublish->lastAttempted()->format('Y-m-d H:i:s.u');

        $failedEventPublish->increaseRetryCount();

        $updatedDate = $failedEventPublish->lastAttempted()->format('Y-m-d H:i:s.u');

        $this->assertNotEquals($initialDate, $updatedDate);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function mark_published_should_set_published_to_true()
    {
        $failedEventPublish = FailedEventPublish::create($this->faker->uuid);

        $failedEventPublish->markPublished();

        $this->assertTrue($failedEventPublish->published());
    }
}
