<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use Ramsey\Uuid\Uuid;

/**
 * Class TransactionId
 * @package ProBillerNG\PurchaseGateway\Domain\Model\Transaction
 */
class TransactionId extends Id
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
     * @throws \Exception
     */
    public static function createFromString(string $value): Id
    {
        return parent::createFromString($value);
    }
}
