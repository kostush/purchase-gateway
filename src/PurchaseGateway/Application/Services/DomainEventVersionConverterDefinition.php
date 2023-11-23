<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services;

interface DomainEventVersionConverterDefinition
{
    /**
     * @param array $payload The event payload
     * @return array
     */
    public function convert(array $payload): array;

    /**
     * Return the latest version
     * @return int
     */
    public function latestVersion(): int;
}
