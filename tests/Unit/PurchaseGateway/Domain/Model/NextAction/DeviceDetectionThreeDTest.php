<?php

namespace Tests\Unit\PurchaseGateway\Domain\Model\NextAction;

use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\DeviceDetectionThreeD;
use ProBillerNG\PurchaseGateway\Domain\Model\ThreeDDeviceCollection;
use Tests\UnitTestCase;

class DeviceDetectionThreeDTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function it_should_contain_the_exact_values_when_to_array_is_called(): void
    {
        $threeDDeviceCollection = $this->createMock(ThreeDDeviceCollection::class);
        $threeDDeviceCollection->method('deviceCollectionUrl')->willReturn('some-url');
        $threeDDeviceCollection->method('deviceCollectionJwt')->willReturn('some-jwt');

        $expectedResult = [
            'type'   => DeviceDetectionThreeD::TYPE,
            'threeD' => [
                'deviceCollectionUrl' => 'some-url',
                'deviceCollectionJWT' => 'some-jwt'
            ]
        ];

        $action = DeviceDetectionThreeD::create($threeDDeviceCollection);
        $this->assertEquals($expectedResult, $action->toArray());
    }
}
