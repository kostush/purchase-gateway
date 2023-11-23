<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Persistence\Doctrine\Mapping\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQL57Platform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\JsonType;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\VarDateTimeImmutableType;
use ProBillerNG\PurchaseGateway\Domain\Model\SubscriptionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SubscriptionInfo;

class DateTimeImmutableMicroseconds extends VarDateTimeImmutableType
{
    /**
     * @param mixed            $value    Value
     * @param AbstractPlatform $platform Platform
     * @return string|null
     * @throws ConversionException
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (\is_object($value)
            && $value instanceof \DateTimeImmutable
        ) {
            $dateTimeFormat = $platform->getDateTimeFormatString();
            return $value->format("{$dateTimeFormat}.u");
        }
        return parent::convertToDatabaseValue($value, $platform);
    }
}
