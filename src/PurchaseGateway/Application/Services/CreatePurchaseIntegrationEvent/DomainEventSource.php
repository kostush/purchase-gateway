<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent;

use ProBillerNG\Projection\Domain\ItemToWorkOn;
use ProBillerNG\Projection\Domain\ReadItemsByBatch;
use ProBillerNG\PurchaseGateway\Domain\EventStore;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;

class DomainEventSource implements ReadItemsByBatch
{
    /**
     * @var EventStore
     */
    private $eventStore;

    /**
     * DomainEventSource constructor.
     * @param EventStore $eventStore Event store
     */
    public function __construct(EventStore $eventStore)
    {
        $this->eventStore = $eventStore;
    }

    /**
     * @param \DateTimeImmutable|null $lastProjectedItemCreationDate Item creation date
     * @param int                     $batchSize                     Batch Size
     *
     * @return ItemToWorkOn[]
     */
    public function nextBatchOfItemsSince(?\DateTimeImmutable $lastProjectedItemCreationDate, int $batchSize): array
    {
        return $this->eventStore->nextBatchOfEventsByTypeSince(
            $lastProjectedItemCreationDate,
            [PurchaseProcessed::class],
            $batchSize
        );
    }
}
