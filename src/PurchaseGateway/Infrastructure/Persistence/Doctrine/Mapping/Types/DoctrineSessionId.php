<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Persistence\Doctrine\Mapping\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;

class DoctrineSessionId extends GuidType
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'SessionId';
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
        if ($value instanceof SessionId) {
            $value = $value->value();
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed            $value    Value
     * @param AbstractPlatform $platform Platform
     * @return mixed|SessionId|\ProBillerNG\PurchaseGateway\Domain\Model\Id
     * @throws \Exception
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return SessionId::createFromString($value);
    }
}
