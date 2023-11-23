<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Projector\ProjectedItem;

use ProBillerNG\Projection\Domain\ProjectedItem;

class BusinessGroup implements ProjectedItem
{
    /** @var string */
    private $businessGroupId;

    /** @var string */
    private $privateKey;

    /** @var array */
    private $publicKeyCollection;

    /** @var string|null */
    private $descriptor;

    /**
     * BusinessGroup constructor.
     * @param string      $businessGroupId     Business group id
     * @param string      $privateKey          Private key
     * @param array       $publicKeyCollection Public collection
     * @param string|null $descriptor          Descriptor
     */
    private function __construct(
        string $businessGroupId,
        string $privateKey,
        array $publicKeyCollection,
        ?string $descriptor
    ) {
        $this->businessGroupId     = $businessGroupId;
        $this->privateKey          = $privateKey;
        $this->publicKeyCollection = $publicKeyCollection;
        $this->descriptor          = $descriptor;
    }

    /**
     * @param string      $businessGroupId     Business group id
     * @param string      $privateKey          Private key
     * @param array       $publicKeyCollection Public collection
     * @param string|null $descriptor          Descriptor
     * @return BusinessGroup
     */
    public static function create(
        string $businessGroupId,
        string $privateKey,
        array $publicKeyCollection,
        ?string $descriptor
    ): BusinessGroup {
        return new static(
            $businessGroupId,
            $privateKey,
            $publicKeyCollection,
            $descriptor
        );
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return $this->businessGroupId;
    }

    /**
     * @return string
     */
    public function privateKey(): string
    {
        return $this->privateKey;
    }

    /**
     * @return array
     */
    public function publicKeyCollection(): array
    {
        return $this->publicKeyCollection;
    }

    /**
     * @return string|null
     */
    public function descriptor(): ?string
    {
        return $this->descriptor;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return __CLASS__;
    }

    /**
     * @return BusinessGroup
     */
    public function representation(): BusinessGroup
    {
        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'businessGroupId'     => $this->id(),
            'privateKey'          => $this->privateKey(),
            'publicKeyCollection' => $this->publicKeyCollection(),
            'descriptor'          => $this->descriptor()
        ];
    }
}
