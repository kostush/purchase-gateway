<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject;

use ProBillerNG\PurchaseGateway\Domain\Projector\ProjectedItem\BusinessGroup;

class BusinessGroupCreated extends Item
{
    const PROJECTED_ENTITY_NAME = BusinessGroup::class;
    const ORIGINAL_EVENT_NAME   = 'ProBillerNG\SiteAdmin\Domain\Model\Event\BusinessGroupCreatedEvent';

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
