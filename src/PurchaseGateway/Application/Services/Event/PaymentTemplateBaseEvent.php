<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\Event;

abstract class PaymentTemplateBaseEvent extends BaseEvent
{
    /**
     * @return array
     */
    abstract public function toArray(): array;
}
