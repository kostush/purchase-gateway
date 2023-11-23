<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO;

use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\HandlerRegistry;
use JMS\Serializer\SerializerBuilder;

use ProBillerNG\PurchaseGateway\Application\Services\IntegrationEvent;
use ProBillerNG\PurchaseGateway\Domain\StoredEvent;

class HttpQueryDTOAssembler implements IntegrationEventDTOAssembler
{
    /**
     * {@inheritdoc}
     *
     * @param array $integrationEvents array
     * @return IntegrationEventRetrieveQueryHttpDTO
     */
    public function assemble(array $integrationEvents)
    {
        $storedEvents = array_map(
            function ($storedEvent) {
                /** @var  StoredEvent $storedEvent */
                return json_decode($storedEvent->eventBody());
            },
            $integrationEvents
        );

        $serializer = SerializerBuilder::create()
            ->addDefaultHandlers()
            ->configureHandlers(
                function (HandlerRegistry $registry) {
                    $classes = [
                        IntegrationEvent::class
                    ];

                    foreach ($classes as $class) {
                        $registry->registerHandler(
                            GraphNavigatorInterface::DIRECTION_SERIALIZATION,
                            $class,
                            'json',
                            function ($visitor, $obj, array $type) {
                                return (string) $obj->value();
                            }
                        );
                    }
                }
            )
            ->setPropertyNamingStrategy(
                new \JMS\Serializer\Naming\SerializedNameAnnotationStrategy(
                    new \JMS\Serializer\Naming\IdenticalPropertyNamingStrategy()
                )
            )
            ->build();

        return IntegrationEventRetrieveQueryHttpDTO::create(
            $storedEvents,
            $serializer
        );
    }
}
