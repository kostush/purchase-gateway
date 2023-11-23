<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject;

use ProBillerNG\PurchaseGateway\Domain\Projector\ProjectedItem\BundleAddon;

class BundleDeleted extends Item
{
    const PROJECTED_ENTITY_NAME = BundleAddon::class;
    const ORIGINAL_EVENT_NAME   = 'ProBillerNG\BundleManagementAdmin\Domain\Model\Event\Bundle\BundleDeletedEvent';

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
