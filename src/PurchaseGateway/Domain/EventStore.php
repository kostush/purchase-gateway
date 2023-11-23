<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain;

interface EventStore
{
    /**
     * @param Event $anEvent Event
     * @return mixed
     */
    public function append(Event $anEvent);

    /**
     * @param \DateTimeImmutable|null $anEventDate Event date
     * @param array                   $type        Event type
     * @param int                     $batchSize   Batch Size
     *
     * @return mixed
     */
    public function nextBatchOfEventsByTypeSince(?\DateTimeImmutable $anEventDate, array $type, int $batchSize);

    /**
     * @param string $aggregateId Aggregate ids
     * @param string $eventType   Event type
     * @return mixed
     */
    public function getByAggregateIdAndType(string $aggregateId, string $eventType);
}
