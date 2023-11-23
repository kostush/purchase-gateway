<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Projector\ProjectedItem;

use ProBillerNG\Projection\Domain\ProjectedItem;

class Addon implements ProjectedItem
{
    /** @var string */
    private $addonId;

    /** @var string|null */
    private $type;

    /**
     * Addon constructor.
     * @param string      $addonId Addon id
     * @param string|null $type    Addon type
     */
    public function __construct(
        string $addonId,
        ?string $type
    ) {
        $this->addonId = $addonId;
        $this->type    = $type;
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return $this->addonId;
    }

    /**
     * @return string|null
     */
    public function type(): ?string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return __CLASS__;
    }

    /**
     * @return Addon
     */
    public function representation(): Addon
    {
        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'addonId' => $this->id(),
            'type' => $this->type()
        ];
    }
}
