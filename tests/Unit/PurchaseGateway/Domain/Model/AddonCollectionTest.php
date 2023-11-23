<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\AddonCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\AddOnId;
use Tests\UnitTestCase;

class AddonCollectionTest extends UnitTestCase
{
    /**
     * @test
     * @throws \ReflectionException
     * @return void
     */
    public function is_valid_object_should_return_true_for_add_on_id(): void
    {
        $class  = new \ReflectionClass(AddonCollection::class);
        $method = $class->getMethod('isValidObject');
        $method->setAccessible(true);
        $this->assertTrue($method->invokeArgs(new AddonCollection(), [AddOnId::createFromString($this->faker->uuid)]));
    }
}
