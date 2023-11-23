<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use ProBillerNG\Logger\Config\FileConfig;
use ProBillerNG\Logger\Log;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

abstract class UnitTestCase extends TestCase
{
    use CreatesApplication;
    use TestDataGenerator;
    use LoadEnv;

    public const CROSS_SALE_SITE_ID = '299d3e6b-cf3d-11e9-8c91-0cc47a283dd2';

    /**
     * Setup function, called before each test
     *
     * @return void
     * @throws \ProBillerNG\SecretManager\Exception\SecretManagerConfigFormatException
     * @throws \ProBillerNG\SecretManager\Exception\SecretManagerConnectionException
     * @throws \ProBillerNG\SecretManager\Exception\SecretManagerRetrieveException
     */
    protected function setUp(): void
    {
        $this->configFaker();
        $this->configLogger();
        $this->loadTestEnv();
        parent::setUp();
    }

    /**
     *
     * @return void
     */
    protected function configLogger()
    {
        $mockConfig = $this->createMock(FileConfig::class);
        $mockConfig->method('getSessionId')->willReturn($this->faker->uuid);
        Log::setConfig($mockConfig);
    }

    /**
     * @param string $class Class
     * @return UuidInterface
     * @throws \Exception
     */
    protected function mockUUIDForClass(string $class): UuidInterface
    {
        $id         = Uuid::uuid4();
        $mockedUUID = \Mockery::mock('alias:' . $class);
        $mockedUUID
            ->shouldReceive('create')
            ->andReturn($id);

        return $id;
    }
}
