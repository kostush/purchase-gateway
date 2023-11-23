<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Persistence\Doctrine\Mapping\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;
use ProBillerNG\PurchaseGateway\Domain\Model\BusinessGroupId;

class DoctrineBusinessGroupId extends GuidType
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'BusinessGroupId';
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
        if ($value instanceof BusinessGroupId) {
            $value = $value->value();
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     *
     * @param mixed            $value    Value
     * @param AbstractPlatform $platform Platform
     * @return mixed|\ProBillerNG\PurchaseGateway\Domain\Model\Id|BusinessGroupId
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): BusinessGroupId
    {
        return BusinessGroupId::createFromString($value);
    }
}
