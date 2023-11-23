<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\AddonId;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonType;
use ProBillerNG\PurchaseGateway\Domain\Model\Bundle;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleId;
use Tests\UnitTestCase;

class BundleTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_a_bundle_when_correct_data_is_sent()
    {
        $this->assertInstanceOf(
            Bundle::class,
            Bundle::create(
                BundleId::create(),
                $this->faker->boolean,
                AddonId::create(),
                AddonType::create(AddonType::CONTENT)
            )
        );
    }
}
