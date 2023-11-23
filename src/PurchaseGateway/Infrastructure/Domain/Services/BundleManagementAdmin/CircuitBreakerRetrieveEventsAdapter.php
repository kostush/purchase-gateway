<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BundleManagementAdmin;

use Odesk\Phystrix\CommandFactory;
use ProBillerNG\CircuitBreaker\CircuitBreaker;
use ProBillerNG\PurchaseGateway\Domain\Services\RetrieveBundleManagementEventsAdapter as RetrieveBundleEventsAdapter;

class CircuitBreakerRetrieveEventsAdapter extends CircuitBreaker implements RetrieveBundleEventsAdapter
{
    /**
     * @var RetrieveBundleManagementEventsAdapter
     */
    private $adapter;

    /**
     * CircuitBreakerRetrieveEventsAdapter constructor.
     * @param CommandFactory                        $commandFactory Command
     * @param RetrieveBundleManagementEventsAdapter $adapter        Adapter
     */
    public function __construct(
        CommandFactory $commandFactory,
        RetrieveBundleManagementEventsAdapter $adapter
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
            RetrieveBundleManagementEventsCommand::class,
            $this->adapter,
            $lastProjectedItemId,
            $batchSize
        );

        return $command->execute();
    }
}
