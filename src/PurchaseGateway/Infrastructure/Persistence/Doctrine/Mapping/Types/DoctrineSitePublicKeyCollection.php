<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Persistence\Doctrine\Mapping\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;
use ProBillerNG\PurchaseGateway\Domain\Model\KeyId;
use ProBillerNG\PurchaseGateway\Domain\Model\PublicKey;
use ProBillerNG\PurchaseGateway\Domain\Model\PublicKeyCollection;

class DoctrineSitePublicKeyCollection extends JsonType
{
    /**
     * @param mixed            $value    Value
     * @param AbstractPlatform $platform Platform
     * @return false|mixed|string|null
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value instanceof PublicKeyCollection) {
            $value = json_encode($value->toArray());
        }

        return $value;
    }

    /**
     * @param mixed            $value    Value
     * @param AbstractPlatform $platform Platform
     * @return mixed|PublicKeyCollection|null
     * @throws \Exception
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): PublicKeyCollection
    {
        if (is_null($value)) {
            return null;
        }

        $publicKeys          = json_decode($value, true);
        $publicKeyCollection = new PublicKeyCollection();

        if (!empty($publicKeys[0])) {
            foreach ($publicKeys as $key => $publicKey) {
                $publicKeyCollection->add(
                    PublicKey::create(
                        KeyId::createFromString($publicKey['key']),
                        \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $publicKey['createdAt']['date'])
                    )
                );
            }
        }

        return $publicKeyCollection;
    }
}
