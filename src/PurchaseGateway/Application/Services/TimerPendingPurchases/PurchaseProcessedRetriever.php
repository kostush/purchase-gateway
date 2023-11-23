<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\TimerPendingPurchases;

use ProBillerNG\Logger\Log;
use ProBillerNG\Projection\Domain\ReadItemsByBatch;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler as SessionHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Session\SessionInfo;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\JsonWebTokenGenerator;

class PurchaseProcessedRetriever implements ReadItemsByBatch
{

    /**
     * @var SessionHandler
     */
    private $purchaseProcessHandler;

    /**
     *
     * /**
     * PurchaseProcessedRetriever constructor.
     * @param SessionHandler $purchaseProcessHandler Site admin service
     */
    public function __construct(SessionHandler $purchaseProcessHandler)
    {
        $this->purchaseProcessHandler = $purchaseProcessHandler;
    }

    /**
     * @param \DateTimeImmutable|null $anEventDate Last projected item id
     * @param int                     $batchSize   event type
     * @return array
     * @throws \Exception
     */
    public function nextBatchOfItemsSince(?\DateTimeImmutable $anEventDate, int $batchSize): array
    {
        $endEventDate = new \DateTimeImmutable();
        $endEventDate = $endEventDate->setTimestamp($endEventDate->getTimestamp() - JsonWebTokenGenerator::TOKEN_TTL);

        $events = $this->purchaseProcessHandler->retrieveSessionsBetween($anEventDate, $endEventDate, $batchSize);

        $itemsToProject = [];

        /**
         * @var SessionInfo $sessionInfo
         */
        foreach ($events as $sessionInfo) {
            $itemsToProject[] = new PurchaseProcessedSession(
                $sessionInfo->id(),
                PurchaseProcess::class,
                \DateTimeImmutable::createFromFormat(
                    'Y-m-d H:i:s.u',
                    $sessionInfo->createdAt()->format('Y-m-d H:i:s.u')
                ),
                $sessionInfo->payload()
            );
        }

        Log::info('My retrieve events from event store', $events);

        return $itemsToProject;
    }
}
