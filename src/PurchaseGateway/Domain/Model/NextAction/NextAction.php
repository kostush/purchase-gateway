<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\NextAction;

/**
 * @codeCoverageIgnore
 */
abstract class NextAction
{
    /**
     * @return string
     */
    public function type(): string
    {
        return static::TYPE;
    }

    /**
     * @return array
     */
    abstract public function toArray(): array;
}
