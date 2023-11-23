<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Persistence\Doctrine\Mapping\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseId;

class DoctrinePurchaseId extends GuidType
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'PurchaseId';
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed            $value    Value
     * @param AbstractPlatform $platform Platform
     * @return mixed|\Ramsey\Uuid\Uuid
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value instanceof PurchaseId) {
            $value = $value->value();
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed            $value    Value
     * @param AbstractPlatform $platform Platform
     * @return mixed|PurchaseId|\ProBillerNG\PurchaseGateway\Domain\Model\Id
     * @throws \Exception
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return PurchaseId::createFromString($value);
    }
}
