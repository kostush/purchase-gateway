<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO;

use JMS\Serializer\SerializerInterface;

class IntegrationEventRetrieveQueryHttpDTO
{
    /**
     * @var string The users json
     */
    private $storedEvents;

    /**
     * IntegrationEventRetrieveQueryHttpDTO constructor.
     * @param array               $storedEventsCollection Stored events
     * @param SerializerInterface $serializer             Serializer interface
     */
    private function __construct(array $storedEventsCollection, SerializerInterface $serializer)
    {
        $this->storedEvents = $serializer->serialize($storedEventsCollection, 'json');
    }

    /**
     * @param array               $storedEventsCollection Stored events
     * @param SerializerInterface $serializer             Serializer interface
     * @return IntegrationEventRetrieveQueryHttpDTO
     */
    public static function create(
        array $storedEventsCollection,
        SerializerInterface $serializer
    ): IntegrationEventRetrieveQueryHttpDTO {
        return new self($storedEventsCollection, $serializer);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->storedEvents;
    }
}
