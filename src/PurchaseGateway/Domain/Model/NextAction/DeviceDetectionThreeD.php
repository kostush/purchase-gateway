<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\NextAction;

use ProBillerNG\PurchaseGateway\Domain\Model\ThreeDDeviceCollection;

class DeviceDetectionThreeD extends NextAction
{
    public const TYPE = 'deviceDetection3D';

    /**
     * @var ThreeDDeviceCollection
     */
    private $threeDDeviceCollection;

    /**
     * DeviceDetectionThreeD constructor.
     * @param ThreeDDeviceCollection $threeDDeviceCollection ThreeD device collection object.
     */
    private function __construct(ThreeDDeviceCollection $threeDDeviceCollection)
    {
        $this->threeDDeviceCollection = $threeDDeviceCollection;
    }

    /**
     * @param ThreeDDeviceCollection $threeDDeviceCollection ThreeD device collection object.
     * @return DeviceDetectionThreeD
     */
    public static function create(ThreeDDeviceCollection $threeDDeviceCollection): self
    {
        return new static($threeDDeviceCollection);
    }

    /**
     * @return ThreeDDeviceCollection
     */
    public function threeDDeviceCollection(): ThreeDDeviceCollection
    {
        return $this->threeDDeviceCollection;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'type'   => $this->type(),
            'threeD' => [
                'deviceCollectionUrl' => $this->threeDDeviceCollection->deviceCollectionUrl(),
                'deviceCollectionJWT' => $this->threeDDeviceCollection->deviceCollectionJwt()
            ],
        ];
    }
}
