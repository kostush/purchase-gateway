<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\AddonType;
use Tests\UnitTestCase;

class AddonTypeTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_an_addon_type_when_correct_data_is_sent()
    {
        $this->assertInstanceOf(
            AddonType::class,
            AddonType::create(AddonType::CONTENT)
        );
    }
}
