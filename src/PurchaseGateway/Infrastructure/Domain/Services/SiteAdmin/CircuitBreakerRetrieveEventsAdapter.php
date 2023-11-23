<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\SiteAdmin;

use ProBillerNG\CircuitBreaker\CircuitBreaker;
use Odesk\Phystrix\CommandFactory;
use ProBillerNG\PurchaseGateway\Domain\Services\RetrieveSiteAdminEventsAdapter as RetrieveAdminEventsAdapter;

class CircuitBreakerRetrieveEventsAdapter extends CircuitBreaker implements RetrieveAdminEventsAdapter
{
    /**
     * @var RetrieveSiteAdminEventsAdapter
     */
    private $adapter;

    /**
     * CircuitBreakerRetrieveEventsAdapter constructor.
     * @param CommandFactory                 $commandFactory Command
     * @param RetrieveSiteAdminEventsAdapter $adapter        Adapter
     */
    public function __construct(
        CommandFactory $commandFactory,
        RetrieveSiteAdminEventsAdapter $adapter
    ) {
        parent::__construct($commandFactory);
        $this->adapter = $adapter;
    }

    /**
     * @param int|null $lastProjectedItemId Last Projected Item Id
     * @param int      $batchSize           Batch Size
     * @return array
     */
    public function retrieveEvents(
        ?int $lastProjectedItemId,
        int $batchSize
    ): array {
        $command = $this->commandFactory->getCommand(
            RetrieveSiteAdminEventsCommand::class,
            $this->adapter,
            $lastProjectedItemId,
            $batchSize
        );

        return $command->execute();
    }
}
