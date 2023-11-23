<?php

declare(strict_types=1);

namespace Tests;

use Laravel\Lumen\Application;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;
use ProBillerNG\Logger\Config\FileConfig;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonId;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonType;
use ProBillerNG\PurchaseGateway\Domain\Model\Bundle;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleId;
use ProBillerNG\PurchaseGateway\Domain\Projector\Repository\BundleRepository;

abstract class IntegrationTestCase extends TestCase
{
    use CreatesApplication;
    use TestDataGenerator;
    use CreateTestableData;
    use LoadEnv;

    public const CROSS_SALE_SITE_ID = '299d3e6b-cf3d-11e9-8c91-0cc47a283dd2';

    /**
     * @var Application
     */
    public $app;

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
        $this->app = $this->createApplication();
        $this->configLogger();
        $this->configFaker();
        $this->loadTestEnv();
        parent::setUp();
    }

    /**
     *
     * @return void
     */
    protected function configLogger()
    {
        $fs = vfsStream::setup();

        $this->configFaker();

        $config = new FileConfig($fs->url() . '/integration_test.log');
        $config->setServiceName('PurchaseGateway');
        $config->setServiceVersion("1");
        $config->setSessionId($this->faker->uuid);
        $config->setLogLevel(100);
        Log::setConfig($config);
    }

    /**
     * @return Bundle
     * @throws \Exception
     */
    protected function createAndAddBundleToRepository(): Bundle
    {
        $bundleRepository = app()->make(BundleRepository::class);

        $bundle = Bundle::create(
            BundleId::createFromString($this->faker->uuid),
            true,
            AddonId::createFromString($this->faker->uuid),
            AddonType::create(AddonType::CONTENT)
        );

        $bundleRepository->add($bundle);
        app('em')->flush();

        return $bundle;
    }

    /**
     * @param Object $subject   Subject
     * @param string $attribute Attribute name
     * @return mixed
     * @throws \ReflectionException
     */
    protected function getProtectedAttributeValue(Object $subject, string $attribute)
    {
        $reflection = new \ReflectionClass(get_class($subject));

        $method = $reflection->getProperty($attribute);
        $method->setAccessible(true);

        return $method->getValue($subject);
    }
}
