<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain;

interface Event
{
    /**
     * @return \DateTimeImmutable
     */
    public function occurredOn(): \DateTimeImmutable;

    /**
     * @return string
     */
    public function aggregateId(): string;

    /**
     * @return int
     */
    public function version(): int;
}
