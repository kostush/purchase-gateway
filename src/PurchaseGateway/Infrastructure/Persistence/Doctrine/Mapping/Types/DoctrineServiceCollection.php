<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Persistence\Doctrine\Mapping\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;
use ProBillerNG\PurchaseGateway\Domain\Model\Service;
use ProBillerNG\PurchaseGateway\Domain\Model\ServiceCollection;

class DoctrineServiceCollection extends JsonType
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
        if ($value instanceof ServiceCollection) {
            $value = json_encode($value->toArray());
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     *
     * @param mixed            $value    Value
     * @param AbstractPlatform $platform Platform
     * @return mixed|ServiceCollection|null
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ServiceCollection
    {
        if (is_null($value)) {
            return null;
        }

        $services = json_decode($value, true);

        $serviceCollection = new ServiceCollection();
        if (!empty($services)) {
            foreach ($services as $key => $service) {
                if (empty($service)) {
                    break;
                }

                $serviceCollection->add(
                    Service::create($service['name'], $service['enabled'], $service['options'] ?? [])
                );
            }
        }

        return $serviceCollection;
    }
}
