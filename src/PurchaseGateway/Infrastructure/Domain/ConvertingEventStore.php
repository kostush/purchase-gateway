<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain;

use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\Exceptions\DomainEventConversionException;
use ProBillerNG\PurchaseGateway\Application\Services\Event\DomainEventVersionConverter;
use ProBillerNG\PurchaseGateway\Domain\Event;
use ProBillerNG\PurchaseGateway\Domain\EventStore;
use ProBillerNG\PurchaseGateway\Domain\StoredEvent;

class ConvertingEventStore implements EventStore
{
    /**
     * @var EventStore
     */
    private $repository;

    /**
     * @var DomainEventVersionConverter
     */
    private $converter;

    /**
     * ConvertingSessionRepository constructor.
     * @param EventStore                  $repository EventStore Repository
     * @param DomainEventVersionConverter $converter  Domain Version Converter
     */
    public function __construct(EventStore $repository, DomainEventVersionConverter $converter)
    {
        $this->repository = $repository;
        $this->converter  = $converter;
    }

    /**
     * @param Event $anEvent The event I want to append
     * @return mixed|void
     * @throws \Doctrine\ORM\ORMException|\Exception
     */
    public function append(Event $anEvent)
    {
        $this->repository->append($anEvent);
    }

    /**
     * @param \DateTimeImmutable|null $anEventDate Event date
     * @param array                   $eventType   Event type
     * @param int                     $batchSize   Batch Size
     * @return mixed
     * @throws \ProBillerNG\Logger\Exception
     */
    public function nextBatchOfEventsByTypeSince(
        ?\DateTimeImmutable $anEventDate,
        array $eventType,
        int $batchSize
    ) {
        $events = $this->repository->nextBatchOfEventsByTypeSince($anEventDate, $eventType, $batchSize);

        foreach ($events as $storedEvent) {
            /** @var StoredEvent $storedEvent */
            try {
                $eventBody = $this->converter->convert(
                    $storedEvent->eventBody(),
                    $storedEvent->typeName()
                );
            } catch (\Exception $e) {
                Log::error($e->getMessage(), $e->getTrace());
                continue;
            }

            $storedEvent->overwriteEventBody($eventBody);
        }

        return $events;
    }

    /**
     * @param string $aggregateId Aggregate ids
     * @param string $eventType   Event type
     * @return mixed
     * @throws \ProBillerNG\Logger\Exception
     * @throws DomainEventConversionException
     */
    public function getByAggregateIdAndType(string $aggregateId, string $eventType)
    {
        $storedEvent = $this->repository->getByAggregateIdAndType($aggregateId, $eventType);

        /** @var StoredEvent $storedEvent */
        try {
            $eventBody = $this->converter->convert(
                $storedEvent->eventBody(),
                $storedEvent->typeName()
            );
        } catch (\Exception $e) {
            Log::error($e->getMessage(), $e->getTrace());
            throw new DomainEventConversionException($e);
        }

        $storedEvent->overwriteEventBody($eventBody);

        return $storedEvent;
    }
}
