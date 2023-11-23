<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

class MemberId extends IdString
{
    /**
     * @param mixed|null $value Value
     * @return self
     * @throws \Exception
     */
    public static function create($value = null): IdString
    {
        return parent::create($value);
    }

    /**
     * @param string $value string
     * @return self
     * @throws \Exception
     */
    public static function createFromString(string $value): IdString
    {
        return parent::createFromString($value);
    }
}
