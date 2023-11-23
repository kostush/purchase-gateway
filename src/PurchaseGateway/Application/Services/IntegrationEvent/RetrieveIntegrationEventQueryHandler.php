<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\IntegrationEvent;

use ProBillerNG\Logger\Log;
use ProBillerNG\Base\Application\Services\QueryHandler;
use ProBillerNG\PurchaseGateway\Application\DTO\IntegrationEventDTOAssembler;
use ProBillerNG\Base\Application\Services\Query;
use ProBillerNG\PurchaseGateway\Application\Services\Event\NetbillingCCPurchaseImportEvent;
use ProBillerNG\PurchaseGateway\Application\Services\Event\RocketgateCCPurchaseImportEvent;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidQueryException;
use ProBillerNG\PurchaseGateway\Domain\EventStore;

class RetrieveIntegrationEventQueryHandler implements QueryHandler
{
    public const BATCH_SIZE = 10;

    /** @var array */
    private $eventTypesMappings = [
        RocketgateCCPurchaseImportEvent::class,
        NetbillingCCPurchaseImportEvent::class
    ];

    /**
     * @var EventStore
     */
    private $eventStoreRepository;

    /**
     * @var IntegrationEventDTOAssembler
     */
    private $dtoAssembler;

    /**
     * RetrieveIntegrationEventQueryHandler constructor.
     *
     * @param EventStore                   $eventStoreRepository Event store repository
     * @param IntegrationEventDTOAssembler $dtoAssembler         DTO assembler
     */
    public function __construct(EventStore $eventStoreRepository, IntegrationEventDTOAssembler $dtoAssembler)
    {
        $this->eventStoreRepository = $eventStoreRepository;
        $this->dtoAssembler         = $dtoAssembler;
    }

    /**
     * @param Query $query Query
     *
     * @return mixed
     * @throws InvalidQueryException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Exception
     */
    public function execute(Query $query)
    {
        if (!$query instanceof RetrieveIntegrationEventQuery) {
            throw new InvalidQueryException(RetrieveIntegrationEventQuery::class, $query);
        }

        try {
            $storedEvents = $this->eventStoreRepository->nextBatchOfEventsByTypeSince(
                $query->eventDate(),
                $this->eventTypesMappings(),
                self::BATCH_SIZE
            );

            return $this->dtoAssembler->assemble($storedEvents);
        } catch (\Exception $e) {
            Log::logException($e);
            throw $e;
        }
    }

    /**
     * @return array
     */
    public function eventTypesMappings(): array
    {
        return $this->eventTypesMappings;
    }
}
