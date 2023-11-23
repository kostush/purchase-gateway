<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

class ThreeDDeviceCollection
{
    /**
     * @var string
     */
    private $deviceCollectionUrl;

    /**
     * @var string
     */
    private $deviceCollectionJwt;

    /**
     * ThreeDDeviceCollection constructor.
     * @param string $deviceCollectionUrl Device collection url.
     * @param string $deviceCollectionJwt Device collection jwt.
     */
    private function __construct(
        string $deviceCollectionUrl,
        string $deviceCollectionJwt
    ) {
        $this->deviceCollectionUrl = $deviceCollectionUrl;
        $this->deviceCollectionJwt = $deviceCollectionJwt;
    }

    /**
     * @param string $deviceCollectionUrl Device collection url.
     * @param string $deviceCollectionJwt Device collection jwt.
     * @return ThreeDDeviceCollection
     */
    public static function create(
        string $deviceCollectionUrl,
        string $deviceCollectionJwt
    ): self {
        return new static($deviceCollectionUrl, $deviceCollectionJwt);
    }

    /**
     * @return string
     */
    public function deviceCollectionUrl(): string
    {
        return $this->deviceCollectionUrl;
    }

    /**
     * @return string
     */
    public function deviceCollectionJwt(): string
    {
        return $this->deviceCollectionJwt;
    }
}
