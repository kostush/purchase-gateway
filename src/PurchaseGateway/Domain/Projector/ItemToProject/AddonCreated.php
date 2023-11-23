<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject;

use ProBillerNG\PurchaseGateway\Domain\Projector\ProjectedItem\Addon;

class AddonCreated extends Item
{
    const PROJECTED_ENTITY_NAME = Addon::class;
    const ORIGINAL_EVENT_NAME   = 'ProBillerNG\BundleManagementAdmin\Domain\Model\Event\Addon\AddonCreatedEvent';

    /**
     * @return string
     */
    public function typeName(): string
    {
        return self::PROJECTED_ENTITY_NAME;
    }

    /**
     * @return string
     */
    public function originalEventName(): string
    {
        return self::ORIGINAL_EVENT_NAME;
    }
}
