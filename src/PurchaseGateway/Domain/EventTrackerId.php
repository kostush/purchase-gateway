<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain;

use ProBillerNG\PurchaseGateway\Domain\Model\Id;
use Ramsey\Uuid\Uuid;

/**
 * Class EventTrackerId
 * @package ProBillerNG\PurchaseGateway\Domain
 */
class EventTrackerId extends Id
{
    /**
     * @param Uuid|null $value Uuid
     * @return self
     * @throws \Exception
     */
    public static function create(Uuid $value = null): Id
    {
        return parent::create($value);
    }

    /**
     * @param string $value string
     * @return self
     *
     * @throws \InvalidArgumentException
     */
    public static function createFromString(string $value): Id
    {
        return parent::createFromString($value);
    }
}
