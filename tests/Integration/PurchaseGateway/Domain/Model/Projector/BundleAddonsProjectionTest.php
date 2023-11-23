<?php

namespace Tests\Integration\PurchaseGateway\Domain\Model\Projector;

use ProBillerNG\PurchaseGateway\Domain\Model\AddonId;
use ProBillerNG\PurchaseGateway\Domain\Model\Bundle;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleId;
use ProBillerNG\PurchaseGateway\Domain\Projector\BundleAddonsProjection;
use ProBillerNG\PurchaseGateway\Domain\Projector\ProjectedItem\Addon;
use ProBillerNG\PurchaseGateway\Domain\Projector\ProjectedItem\BundleAddon;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Projector\DoctrineAddonProjectionRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Projector\DoctrineBundleProjectionRepository;
use Tests\IntegrationTestCase;

class BundleAddonsProjectionTest extends IntegrationTestCase
{
    /**
     * @var DoctrineAddonProjectionRepository
     */
    private $doctrineAddonProjectionRepository;

    /**
     * @var DoctrineBundleProjectionRepository
     */
    private $doctrineBundleProjectionRepository;

    /**
     * @var BundleAddonsProjection
     */
    private $bundleAddonsProjection;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->doctrineAddonProjectionRepository = new DoctrineAddonProjectionRepository(
            app('em'),
            app('em')->getClassMetadata(Addon::class)
        );

        $this->doctrineBundleProjectionRepository = new DoctrineBundleProjectionRepository(
            app('em'),
            app('em')->getClassMetadata(Bundle::class)
        );

        $this->bundleAddonsProjection = new BundleAddonsProjection(
            $this->doctrineAddonProjectionRepository,
            $this->doctrineBundleProjectionRepository
        );
    }

    /**
     * @test
     * @return string
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_add_addon_when_addon_created_is_triggered(): string
    {
        $addon = new Addon(
            $this->faker->uuid,
            'content'
        );

        $this->bundleAddonsProjection->whenAddonCreated($addon);

        app('em')->flush();

        $retrievedAddon = $this->doctrineAddonProjectionRepository->find($addon->id());

        $this->assertEquals($addon, $retrievedAddon);

        return $addon->id();
    }

    /**
     * @test
     * @depends it_should_add_addon_when_addon_created_is_triggered
     * @param string $addonId Addon id
     * @return array
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Exception
     */
    public function it_should_add_bundle_when_bundle_created_is_triggered(string $addonId): array
    {
        $bundle = new BundleAddon(
            $this->faker->uuid,
            true,
            [$addonId]
        );

        $this->bundleAddonsProjection->whenBundleCreated($bundle);

        app('em')->flush();

        $retrievedBundle = $this->doctrineBundleProjectionRepository->findBundleByBundleAddon(
            BundleId::createFromString($bundle->id()),
            AddonId::createFromString($addonId)
        );

        $this->assertSame($bundle->id(), $retrievedBundle->id());

        return [
            'bundle'          => $bundle,
            'retrievedBundle' => $retrievedBundle
        ];
    }

    /**
     * @test
     * @depends it_should_add_bundle_when_bundle_created_is_triggered
     * @param array $addBundleAddonResult Result of bundle addon add
     * @return void
     */
    public function it_should_have_correct_require_active_content_flag_when_bundle_was_added_succesfully(
        array $addBundleAddonResult
    ): void {
        $this->assertTrue($addBundleAddonResult['retrievedBundle']->isRequireActiveContent());
    }

    /**
     * @test
     * @depends it_should_add_bundle_when_bundle_created_is_triggered
     * @param array $addBundleAddonResult Result of bundle addon add
     * @return void
     */
    public function it_should_have_correct_the_correct_addon_id_when_bundle_was_added_succesfully(
        array $addBundleAddonResult
    ): void {
        $this->assertSame(
            $addBundleAddonResult['bundle']->addons()[0],
            (string) $addBundleAddonResult['retrievedBundle']->addonId()
        );
    }

    /**
     * @test
     * @depends it_should_add_addon_when_addon_created_is_triggered
     * @param string $addonId Addon id
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_update_addon_when_addon_updated_is_triggered(string $addonId): void
    {
        $addon = new Addon(
            $addonId,
            'feature'
        );

        $this->bundleAddonsProjection->whenAddonUpdated($addon);

        app('em')->flush();

        $retrievedAddon = $this->doctrineAddonProjectionRepository->find($addon->id());

        $this->assertEquals($addon, $retrievedAddon);
    }

    /**
     * @test
     * @depends it_should_add_bundle_when_bundle_created_is_triggered
     * @param array $addBundleAddonResult Result of bundle addon add
     * @return array
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Exception
     */
    public function it_should_update_bundle_when_bundle_updated_is_triggered(array $addBundleAddonResult): array
    {
        $bundle = new BundleAddon(
            $addBundleAddonResult['retrievedBundle']->id(),
            false,
            [(string) $addBundleAddonResult['retrievedBundle']->addonId()]
        );

        $this->bundleAddonsProjection->whenBundleUpdated($bundle);

        app('em')->flush();

        $retrievedBundle = $this->doctrineBundleProjectionRepository->findBundleByBundleAddon(
            BundleId::createFromString($bundle->id()),
            AddonId::createFromString($bundle->addons()[0])
        );

        $this->assertSame($bundle->id(), $retrievedBundle->id());

        return [
            'bundle'          => $bundle,
            'retrievedBundle' => $retrievedBundle
        ];
    }

    /**
     * @test
     * @depends it_should_update_bundle_when_bundle_updated_is_triggered
     * @param array $updateBundleAddonResult Result of bundle addon update
     * @return void
     */
    public function it_should_have_correct_require_active_content_flag_when_bundle_was_updated_succesfully(
        array $updateBundleAddonResult
    ): void {
        $this->assertFalse($updateBundleAddonResult['retrievedBundle']->isRequireActiveContent());
    }

    /**
     * @test
     * @depends it_should_update_bundle_when_bundle_updated_is_triggered
     * @param array $updateBundleAddonResult Result of bundle addon update
     * @return void
     */
    public function it_should_have_correct_the_correct_addon_id_when_bundle_was_updated_succesfully(
        array $updateBundleAddonResult
    ): void {
        $this->assertSame(
            $updateBundleAddonResult['bundle']->addons()[0],
            (string) $updateBundleAddonResult['retrievedBundle']->addonId()
        );
    }

    /**
     * @test
     * @depends it_should_add_addon_when_addon_created_is_triggered
     * @param string $addonId Addon id
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_delete_addon_when_addon_deleted_is_triggered(string $addonId): void
    {
        $addon = new Addon(
            $addonId,
            'feature'
        );

        $this->bundleAddonsProjection->whenAddonDeleted($addon);

        app('em')->flush();

        $retrievedAddon = $this->doctrineAddonProjectionRepository->find($addon->id());

        $this->assertEmpty($retrievedAddon);
    }

    /**
     * @test
     * @depends it_should_update_bundle_when_bundle_updated_is_triggered
     * @param array $updateBundleAddonResult Addon id
     * @return void
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Exception
     */
    public function it_should_delete_bundle_when_bundle_deleted_is_triggered(array $updateBundleAddonResult): void
    {
        $bundle = new BundleAddon(
            $updateBundleAddonResult['retrievedBundle']->id(),
            false,
            [(string) $updateBundleAddonResult['retrievedBundle']->addonId()]
        );

        $this->bundleAddonsProjection->whenBundleUpdated($bundle);

        app('em')->flush();

        $retrievedBundle = $this->doctrineBundleProjectionRepository->findBundleByBundleAddon(
            BundleId::createFromString($bundle->id()),
            AddonId::createFromString($bundle->addons()[0])
        );

        $this->assertEmpty($retrievedBundle);
    }
}
