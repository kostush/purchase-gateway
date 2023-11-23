<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Repository;

use ProBillerNG\PurchaseGateway\Domain\Model\Session\SessionInfo;
use Ramsey\Uuid\UuidInterface;

interface PurchaseProcessRepositoryInterface
{
    /**
     * @param UuidInterface $sessionId The uuid object
     * @return SessionInfo
     */
    public function findOne(UuidInterface $sessionId);

    /**
     * @param SessionInfo $session The session info object
     * @return mixed
     */
    public function create(SessionInfo $session): bool;

    /**
     * @param SessionInfo $session The session info object
     * @return bool
     */
    public function update(SessionInfo $session): bool;

    /**
     * @param \DateTimeImmutable|null $anEventDate  Start event date
     * @param \DateTimeImmutable      $endEventDate End event date
     * @param int                     $batchSize    Bach size
     * @return array
     * @throws \Exception
     */
    public function retrieveSessionsBetween(
        ?\DateTimeImmutable $anEventDate,
        \DateTimeImmutable $endEventDate,
        int $batchSize
    ): array;
}
