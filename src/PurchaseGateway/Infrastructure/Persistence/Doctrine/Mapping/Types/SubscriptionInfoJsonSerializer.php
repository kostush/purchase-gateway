<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Persistence\Doctrine\Mapping\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;
use ProBillerNG\PurchaseGateway\Domain\Model\SubscriptionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SubscriptionInfo;

class SubscriptionInfoJsonSerializer extends JsonType
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'subscriptionInfo';
    }

    /**
     * @param mixed            $value    object
     * @param AbstractPlatform $platform abstract platform
     * @return mixed|null|string
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        /** @var SubscriptionInfo $value */
        return json_encode(
            [
                'subscriptionId' => ($value ? (string) $value->subscriptionId() : null),
                'username'       => $value ? $value->username() : null
            ]
        );
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

        if (!empty($dbResult['subscriptionId'])) {
            return SubscriptionInfo::create(
                SubscriptionId::createFromString($dbResult['subscriptionId']),
                $dbResult['username']
            );
        }
        return null;
    }
}
