<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\Projection\Domain\ProjectedItem;

class Bundle implements ProjectedItem
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var BundleId
     */
    private $bundleId;

    /**
     * @var bool
     */
    private $requireActiveContent;

    /**
     * @var AddonId
     */
    private $addonId;

    /**
     * @var AddonType
     */
    private $addonType;

    /**
     * Bundle constructor.
     *
     * @param BundleId  $bundleId             Bundle id.
     * @param bool      $requireActiveContent Require Active Content.
     * @param AddonId   $addonId              Addon id.
     * @param AddonType $addonType            Addon type.
     */
    private function __construct(
        BundleId $bundleId,
        bool $requireActiveContent,
        AddonId $addonId,
        AddonType $addonType
    ) {
        $this->bundleId             = $bundleId;
        $this->requireActiveContent = $requireActiveContent;
        $this->addonId              = $addonId;
        $this->addonType            = $addonType;
    }

    /**
     * @param BundleId  $bundleId             Bundle id.
     * @param bool      $requireActiveContent Require Active Content.
     * @param AddonId   $addonId              Addon id.
     * @param AddonType $addonType            Addon type.
     *
     * @return self
     */
    public static function create(
        BundleId $bundleId,
        bool $requireActiveContent,
        AddonId $addonId,
        AddonType $addonType
    ): self {
        return new static(
            $bundleId,
            $requireActiveContent,
            $addonId,
            $addonType
        );
    }

    /**
     * @return BundleId
     * @codeCoverageIgnore
     */
    public function bundleId(): BundleId
    {
        return $this->bundleId;
    }

    /**
     * @return bool
     * @codeCoverageIgnore
     */
    public function isRequireActiveContent(): bool
    {
        return $this->requireActiveContent;
    }

    /**
     * @return AddonId
     * @codeCoverageIgnore
     */
    public function addonId(): AddonId
    {
        return $this->addonId;
    }

    /**
     * @return AddonType
     * @codeCoverageIgnore
     */
    public function addonType(): AddonType
    {
        return $this->addonType;
    }

    /**
     * @param string $addonType Addon type.
     * @return void
     * @codeCoverageIgnore
     */
    public function setAddonType(string $addonType)
    {
        $this->addonType = AddonType::create($addonType);
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return __CLASS__;
    }

    /**
     * @return Bundle
     */
    public function representation(): Bundle
    {
        return $this;
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return (string) $this->bundleId;
    }
}
