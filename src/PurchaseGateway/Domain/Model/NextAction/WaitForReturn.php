<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\NextAction;

class WaitForReturn extends NextAction
{
    public const TYPE = 'waitForReturn';

    /**
     * @return WaitForReturn
     */
    public static function create(): self
    {
        return new static();
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return ['type' => $this->type()];
    }
}
