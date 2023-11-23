<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Projector\ProjectedItem;

use ProBillerNG\Projection\Domain\ProjectedItem;

class BundleAddon implements ProjectedItem
{
    /** @var string */
    private $bundleId;

    /** @var bool|null */
    private $requireActiveContent;

    /**
     * @var array|null
     */
    private $addons;

    /**
     * BundleAddon constructor.
     * @param string       $bundleId             BundleAddon id
     * @param boolean|null $requireActiveContent Require active content
     * @param array|null   $addons               Addons
     */
    public function __construct(
        string $bundleId,
        ?bool $requireActiveContent,
        ?array $addons
    ) {
        $this->bundleId             = $bundleId;
        $this->requireActiveContent = (bool) $requireActiveContent;
        $this->addons               = $addons;
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return $this->bundleId;
    }

    /**
     * @return bool
     */
    public function requireActiveContent(): bool
    {
        return $this->requireActiveContent;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return __CLASS__;
    }

    /**
     * @return array
     */
    public function addons(): array
    {
        return $this->addons;
    }


    /**
     * @return BundleAddon
     */
    public function representation(): BundleAddon
    {
        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'bundleId'             => $this->id(),
            'requireActiveContent' => $this->requireActiveContent(),
            'addons'               => $this->addons()
        ];
    }
}
