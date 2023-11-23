<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Projector\ProjectedItem;

use ProBillerNG\PurchaseGateway\Domain\Model\AddonType;
use ProBillerNG\PurchaseGateway\Domain\Projector\ProjectedItem\Addon;
use Tests\UnitTestCase;

class AddonTest extends UnitTestCase
{
    /**
     * @test
     * @return Addon
     * @throws \Exception
     */
    public function it_should_return_an_addon_projection(): Addon
    {
        $addon = new Addon($this->faker->uuid, AddonType::CONTENT);

        $this->assertInstanceOf(Addon::class, $addon);

        return $addon;
    }

    /**
     * @test
     * @depends it_should_return_an_addon_projection
     * @param Addon $addon Addon
     * @return void
     */
    public function it_should_contain_the_id_key(Addon $addon): void
    {
        $this->assertArrayHasKey('addonId', $addon->toArray());
    }

    /**
     * @test
     * @depends it_should_return_an_addon_projection
     * @param Addon $addon Addon
     * @return void
     */
    public function it_should_contain_the_type_key(Addon $addon): void
    {
        $this->assertArrayHasKey('type', $addon->toArray());
    }
}
