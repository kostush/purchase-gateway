<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Testing\TestCase;
use org\bovigo\vfs\vfsStream;
use ProBillerNG\Logger\Config\FileConfig;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonId;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonType;
use ProBillerNG\PurchaseGateway\Domain\Model\Bundle;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleId;
use ProBillerNG\PurchaseGateway\Domain\Model\Service;
use ProBillerNG\PurchaseGateway\Domain\Model\ServiceCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Projector\Repository\BundleRepository;
use ProBillerNG\PurchaseGateway\Domain\Projector\Repository\SiteRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use Ramsey\Uuid\Uuid;

abstract class SystemTestCase extends TestCase
{
    use CreatesApplication;
    use ClearSingletons;
    use ClearConnections;
    use Faker;
    use TestEnvironmentSetup;
    use LoadEnv;

    /**
     * Setup function, called before each test
     * @return void
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->configFaker();
        $this->configLogger();
        $this->loadTestEnv();
    }

    /**
     * @return void
     */
    protected function clearStoredEventsTable(): void
    {
        DB::table('stored_events')->truncate();
    }

    /**
     * @return void
     *
     * @throws \Exception
     */
    protected function configLogger()
    {
        $fs = vfsStream::setup();

        $config = new FileConfig($fs->url() . '/system_tests.log');
        $config->setServiceName(config('app.name'));
        $config->setServiceVersion(config('app.version'));
        $config->setSessionId(Uuid::uuid4()->toString());
        $config->setLogLevel(100);
        Log::setConfig($config);
    }

    /**
     * Regular teardown
     * @return void
     */
    protected function tearDown(): void
    {
        $this->clearConnections();
        $this->clearSingleton();
        parent::tearDown();
    }

    /**
     * @param array $data Data to overwrite
     * @return Bundle
     * @throws \Exception
     */
    protected function createAndAddBundleToRepository($data = []): Bundle
    {
        $bundleRepository = app()->make(BundleRepository::class);

        if (!empty($data['bundleId']) && !empty($data['addonId'])) {
            $bundle = $bundleRepository->findBundleByBundleAddon(
                BundleId::createFromString($data['bundleId']),
                AddonId::createFromString($data['addonId'])
            );

            if (!empty($bundle)) {
                return $bundle;
            }
        }

        $bundle = Bundle::create(
            BundleId::createFromString($data['bundleId'] ?? $this->faker->uuid),
            true,
            AddonId::createFromString($data['addonId'] ?? $this->faker->uuid),
            AddonType::create($data['addonType'] ?? AddonType::CONTENT)
        );

        $bundleRepository->add($bundle);
        app('em')->flush();

        return $bundle;
    }

    /**
     * For system tests we need to be able to reproduce cases when the process purchase is blocked due to fraud rules
     * This function will enable/disable fraud rules for a specific site
     *
     * @param string $siteId
     * @param bool   $enable
     *
     * @return Site
     * @throws \Exception
     * @deprecated  This should be not used anymore, since PG started use ConfigService instead mysql this
     *              function doesn't do anything. It doesn't update the data anymore and it should not do that for
     *              config-service.
     */
    protected function updateFraudServiceStatus(string $siteId, bool $enable = false): Site
    {
        /** @var ConfigService $configService */
        $configService = app()->make(ConfigService::class);
        $site          = $configService->getSite($siteId);

        $newArrayCollection = new ServiceCollection();

        $updateDB = false;

        foreach ($site->serviceCollection() as $key => $service) {
            if (($service->name() == 'fraud') && ($service->enabled() != $enable)) {
                $newArrayCollection->add(Service::create($service->name(), $enable));
                $updateDB = true;
            } else {
                $newArrayCollection->add(Service::create($service->name(), $service->enabled()));
            }
        }

        // update only if needed
        if ($updateDB) {
            $newSite = Site::create(
                $site->siteId(),
                $site->businessGroupId(),
                $site->url(),
                $site->name(),
                $site->phoneNumber(),
                $site->skypeNumber(),
                $site->supportLink(),
                $site->mailSupportLink(),
                $site->messageSupportLink(),
                $site->cancellationLink(),
                $site->postbackUrl(),
                $newArrayCollection,
                $site->privateKey(),
                $site->publicKeyCollection(),
                $site->descriptor(),
                $site->isStickyGateway(),
                false,
                Site::DEFAULT_NUMBER_OF_ATTEMPTS
            );

//            $siteRepository->delete($site);
//            app('em')->flush();
//
//            $siteRepository->add($newSite);
//            app('em')->flush();

            return $newSite;
        }

        return $site;
    }

    /**
     * @param string $siteId Site id
     *
     * @return bool
     * @throws \Exception
     */
    protected function isFraudServiceEnabled(string $siteId): bool
    {
        /** @var ConfigService $configService */
        $configService = app()->make(ConfigService::class);
        $site          = $configService->getSite($siteId);

        return $site->isFraudServiceEnabled();
    }
}
