<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Repository\InMemory;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processing;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Repository\InMemory\RedisRepository;
use Redis;
use Tests\UnitTestCase;

class RedisRepositoryTest extends UnitTestCase
{
    /**
     * @var $redis RedisRepository
     */
    protected $redis;

    /**
     * @return void
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        // Connect and authenticate into the Redis Server
        $this->redis = new RedisRepository();
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_connect_to_redis(): void
    {
        $this->assertTrue($this->redis->isConnected());
    }

    /**
     * @test
     *
     * @return string
     * @throws Exception
     */
    public function it_should_return_true_when_session_id_is_set(): string
    {
        $sessionId = $this->faker->uuid;

        $isKeyStored = $this->redis->storeSessionId($sessionId);

        self::assertTrue($isKeyStored);

        return $sessionId;
    }

    /**
     * @test
     * @depends it_should_return_true_when_session_id_is_set
     * @param string $sessionId Session id
     * @return void
     * @throws Exception
     */
    public function it_should_return_false_when_session_id_is_set_the_second_time(string $sessionId): void
    {
        $isKeyStored = $this->redis->storeSessionId($sessionId);

        self::assertFalse($isKeyStored);

        $this->redis->deleteSessionId($sessionId);
    }

    /**
     * @test
     * @covers ::storePurchaseStatus
     *
     * @return string
     * @throws Exception
     */
    public function it_should_return_true_when_purchase_status_key_is_set(): string
    {
        $sessionId = $this->faker->uuid;

        $isKeyStored = $this->redis->storePurchaseStatus($sessionId, Processing::name());

        $this->assertTrue($isKeyStored);

        return $sessionId;
    }

    /**
     * @test
     * @covers ::retrievePurchaseStatus
     * @return void
     * @throws Exception
     */
    public function it_should_return_empty_string_if_key_is_not_found(): void
    {
        $keyRetrieved = $this->redis->retrievePurchaseStatus($this->faker->uuid);

        $this->assertEmpty($keyRetrieved);
    }

    /**
     * @test
     *
     * @param string $sessionId Session id
     *
     * @covers ::retrievePurchaseStatus
     * @depends it_should_return_true_when_purchase_status_key_is_set
     * @return void
     * @throws Exception
     */
    public function it_should_return_the_value_if_the_key_is_found(string $sessionId): void
    {
        $keyRetrieved = $this->redis->retrievePurchaseStatus($sessionId);

        $this->assertEquals(Processing::name(), $keyRetrieved);
    }

    /**
     * @test
     * @covers ::deletePurchaseStatus
     * @return void
     * @throws Exception
     */
    public function it_should_return_zero_when_no_key_deleted(): void
    {
        $keyRetrieved = $this->redis->deletePurchaseStatus($this->faker->uuid);

        $this->assertEquals(0, $keyRetrieved);
    }

    /**
     * @test
     *
     * @param string $sessionId Session id
     *
     * @return void
     * @throws Exception
     * @covers ::deletePurchaseStatus
     * @depends it_should_return_true_when_purchase_status_key_is_set
     */
    public function it_should_return_one_when_the_key_is_found_and_deleted(string $sessionId): void
    {
        $keyRetrieved = $this->redis->deletePurchaseStatus($sessionId);

        $this->assertEquals(1, $keyRetrieved);
    }

    /**
     * @return void
     * @throws Exception
     * @todo test
     */
    public function it_should_return_false_when_purchase_status_key_is_set(): void
    {
        $mockRedis = $this->createMock(Redis::class);
        $mockRedis->method('set')->willReturn(null);

        /** @var RedisRepository $redis */
        $redis       = app()->instance(RedisRepository::class, $mockRedis);
        $isKeyStored = $redis->storePurchaseStatus($this->faker->uuid, Processing::name());

        $this->assertFalse($isKeyStored);
    }
}
