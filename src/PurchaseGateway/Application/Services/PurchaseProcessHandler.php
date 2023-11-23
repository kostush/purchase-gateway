<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services;

use DateTimeImmutable;
use Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;

interface PurchaseProcessHandler
{
    /**
     * @param PurchaseProcess|null $purchaseProcess The purchase process
     *
     * @return bool
     */
    public function create(?PurchaseProcess $purchaseProcess): bool;

    /**
     * @param string $sessionId Session Id
     * @return PurchaseProcess
     */
    public function load(string $sessionId): PurchaseProcess;

    /**
     * @param PurchaseProcess $purchaseProcess Purchase Process
     * @return bool
     */
    public function update(PurchaseProcess $purchaseProcess): bool;

    /**
     * @param DateTimeImmutable|null $anEventDate  Start event date
     * @param DateTimeImmutable      $endEventDate End event date
     * @param int                    $batchSize    Bach size
     * @return array
     * @throws Exception
     */
    public function retrieveSessionsBetween(
        ?DateTimeImmutable $anEventDate,
        DateTimeImmutable $endEventDate,
        int $batchSize
    ): array;
}
