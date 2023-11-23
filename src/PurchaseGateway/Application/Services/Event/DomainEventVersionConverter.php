<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\Event;

use ProBillerNG\PurchaseGateway\Application\Services\DomainEventVersionConverterDefinition;
use ProBillerNG\PurchaseGateway\Application\Services\Event\Versioning\PurchaseProcessedConverter;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;

class DomainEventVersionConverter
{
    /**
     * @var DomainEventVersionConverterDefinition
     */
    private $converterClass;

    /**
     * Set domain event converter instance by the event type
     * @param string $eventType The event type
     * @return void
     */
    private function getConverterClassByEventType(string $eventType): void
    {
        switch ($eventType) {
            case PurchaseProcessed::class:
                $this->converterClass = new PurchaseProcessedConverter();
                break;
        }
    }


    /**
     * @param string $payload   The event payload as JSON
     * @param string $eventType The event type
     * @return string The payload as JSON
     */
    public function convert(string $payload, string $eventType): string
    {
        $this->getConverterClassByEventType($eventType);

        if ($this->converterClass instanceof DomainEventVersionConverterDefinition) {
            return json_encode(
                $this->converterClass->convert(json_decode($payload, true))
            );
        }

        return $payload;
    }


    /**
     * @param string $eventType asd
     * @return int
     */
    public function getVersionByType(string $eventType): int
    {
        $this->getConverterClassByEventType($eventType);

        if ($this->converterClass instanceof DomainEventVersionConverterDefinition) {
            return $this->converterClass->latestVersion();
        }

        return 1;
    }
}
