<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Projector\ProjectedItem;

use ProBillerNG\PurchaseGateway\Domain\Projector\BusinessGroupSitesProjection;
use Tests\UnitTestCase;
use ProBillerNG\PurchaseGateway\Domain\Projector\ProjectedItem\BusinessGroup;

class BusinessGroupTest extends UnitTestCase
{
    /**
     * @test
     * @return BusinessGroup
     */
    public function it_should_return_business_group_projection(): BusinessGroup
    {
        $businessGroup = BusinessGroup::create(
            $this->faker->uuid,
            'ab3708dc-1415-4654-9403-a4108999a80a',
            [
                'key'       => '3dcc4a19-e2a8-4622-8e03-52247bbd302d',
                'createdAt' => '2019-11-15T16:05:45+00:00'
            ],
            'Business group descriptor'
        );

        $this->assertInstanceOf(BusinessGroup::class, $businessGroup);

        return $businessGroup;
    }

    /**
     * @test
     * @depends it_should_return_business_group_projection
     * @param BusinessGroup $businessGroup Business group
     * @return void
     */
    public function it_should_return_a_business_group_with_business_group_id_key(BusinessGroup $businessGroup): void
    {
        $this->assertArrayHasKey('businessGroupId', $businessGroup->toArray());
    }

    /**
     * @test
     * @depends it_should_return_business_group_projection
     * @param BusinessGroup $businessGroup Business group
     * @return void
     */
    public function it_should_return_a_business_group_with_private_key(BusinessGroup $businessGroup): void
    {
        $this->assertArrayHasKey('privateKey', $businessGroup->toArray());
    }

    /**
     * @test
     * @depends it_should_return_business_group_projection
     * @param BusinessGroup $businessGroup Business group
     * @return void
     */
    public function it_should_return_a_business_group_with_public_key_collection(BusinessGroup $businessGroup): void
    {
        $this->assertArrayHasKey('publicKeyCollection', $businessGroup->toArray());
    }

    /**
     * @test
     * @depends it_should_return_business_group_projection
     * @param BusinessGroup $businessGroup Business group
     * @return void
     */
    public function it_should_return_a_business_group_with_descriptor_key(BusinessGroup $businessGroup): void
    {
        $this->assertArrayHasKey('descriptor', $businessGroup->toArray());
    }
}
