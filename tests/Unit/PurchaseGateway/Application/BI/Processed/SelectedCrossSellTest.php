<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\BI\Processed;

use ProBillerNG\PurchaseGateway\Application\BI\Processed\SelectedCrossSell;
use Tests\UnitTestCase;

class SelectedCrossSellTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function it_should_return_a_valid_selected_cross_sell_object()
    {
        $selectedCrossSell = SelectedCrossSell::create(
            'active',
            $this->faker->uuid,
            $this->faker->uuid,
            $this->faker->uuid,
            $this->faker->uuid,
            15,
            59.99,
            30,
            89.99,
            $this->faker->uuid,
            [],
            $this->faker->uuid,
            null
        );

        $this->assertInstanceOf(SelectedCrossSell::class, $selectedCrossSell);
    }
}
