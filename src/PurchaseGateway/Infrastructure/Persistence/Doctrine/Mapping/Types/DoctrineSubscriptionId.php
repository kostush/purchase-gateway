<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Persistence\Doctrine\Mapping\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;
use ProBillerNG\PurchaseGateway\Domain\Model\SubscriptionId;

class DoctrineSubscriptionId extends GuidType
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'SubscriptionId';
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
        if ($value instanceof SubscriptionId) {
            $value = $value->value();
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed            $value    Value
     * @param AbstractPlatform $platform Platform
     * @return mixed|SubscriptionId|\ProBillerNG\PurchaseGateway\Domain\Model\Id
     * @throws \Exception
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return SubscriptionId::createFromString($value);
    }
}
