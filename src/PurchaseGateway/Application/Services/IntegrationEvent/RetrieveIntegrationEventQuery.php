<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\IntegrationEvent;

use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidDateTimeException;
use ProBillerNG\Base\Application\Services\Query;

class RetrieveIntegrationEventQuery extends Query
{
    /**
     * @var \DateTimeImmutable
     */
    private $eventDate;

    /**
     * RetrieveIntegrationEventQuery constructor.
     * @param string $eventDate Event Date
     * @throws \Exception
     */
    public function __construct(
        string $eventDate
    ) {
        $this->eventDate = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $eventDate);

        if ($this->eventDate === false) {
            throw new InvalidDateTimeException();
        }
    }

    /**
     * @return \DateTimeImmutable
     */
    public function eventDate(): \DateTimeImmutable
    {
        return $this->eventDate;
    }
}
