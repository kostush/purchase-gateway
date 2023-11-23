<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Persistence\Doctrine\Mapping\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonId;

class AddonCollectionJsonSerializer extends JsonType
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'addonCollection';
    }

    /**
     * @param mixed            $value    object
     * @param AbstractPlatform $platform abstract platform
     * @return mixed|null|string
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        $addons = [];

        foreach ($value->toArray() as $addon) {
            $addons[] = (string) $addon->value();
        }

        return json_encode($addons);
    }

    /**
     * @param mixed            $value    json
     * @param AbstractPlatform $platform abstract platform
     * @return mixed|null
     * @throws \Exception
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        $dbResult = json_decode($value, true);

        $addons = new AddonCollection();

        foreach ($dbResult as $value) {
            $addonId = AddonId::createFromString($value);
            $addons->add($addonId);
        }

        return $addons;
    }
}
