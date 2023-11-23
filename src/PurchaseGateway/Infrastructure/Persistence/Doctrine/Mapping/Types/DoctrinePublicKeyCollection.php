<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Persistence\Doctrine\Mapping\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;
use ProBillerNG\PurchaseGateway\Domain\Model\PublicKeyCollection;

class DoctrinePublicKeyCollection extends JsonType
{
    /**
     * {@inheritdoc}
     *
     * @param mixed            $value    Value
     * @param AbstractPlatform $platform Platform
     * @return mixed|\Ramsey\Uuid\Uuid
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return json_encode($value);
    }

    /**
     * @param mixed            $value    Value
     * @param AbstractPlatform $platform Platform
     * @return array|null
     * @throws \Exception
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?array
    {
        if (is_null($value)) {
            return null;
        }
        $publicKeys = json_decode($value, true);

        return $publicKeys;
    }
}
