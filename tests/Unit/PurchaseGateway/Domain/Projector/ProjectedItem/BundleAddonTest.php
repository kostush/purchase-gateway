<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Projector\ProjectedItem;

use ProBillerNG\PurchaseGateway\Domain\Projector\ProjectedItem\BundleAddon;
use Tests\UnitTestCase;

class BundleAddonTest extends UnitTestCase
{
    /**
     * @test
     * @return BundleAddon
     * @throws \Exception
     */
    public function it_should_return_a_bundle_addon_projection_created(): BundleAddon
    {
        $bundleAddon = new BundleAddon($this->faker->uuid, true, [$this->faker->uuid]);

        $this->assertInstanceOf(BundleAddon::class, $bundleAddon);

        return $bundleAddon;
    }

    /**
     * @test
     * @depends it_should_return_a_bundle_addon_projection_created
     * @param BundleAddon $bundleAddon Bundle addon
     * @return void
     */
    public function it_should_contain_the_bundle_id_key(BundleAddon $bundleAddon): void
    {
        $this->assertArrayHasKey('bundleId', $bundleAddon->toArray());
    }

    /**
     * @test
     * @depends it_should_return_a_bundle_addon_projection_created
     * @param BundleAddon $bundleAddon Bundle addon
     * @return void
     */
    public function it_should_contain_the_require_active_content_key(BundleAddon $bundleAddon): void
    {
        $this->assertArrayHasKey('requireActiveContent', $bundleAddon->toArray());
    }

    /**
     * @test
     * @depends it_should_return_a_bundle_addon_projection_created
     * @param BundleAddon $bundleAddon Bundle addon
     * @return void
     */
    public function it_should_contain_the_addons_key(BundleAddon $bundleAddon): void
    {
        $this->assertArrayHasKey('addons', $bundleAddon->toArray());
    }
}
