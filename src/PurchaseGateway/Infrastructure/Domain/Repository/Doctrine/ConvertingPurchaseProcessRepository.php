<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Repository\Doctrine;

use ProBillerNG\PurchaseGateway\Application\Services\SessionVersionConverter;
use ProBillerNG\PurchaseGateway\Domain\Model\Session\SessionInfo;
use ProBillerNG\PurchaseGateway\Domain\Repository\PurchaseProcessRepositoryInterface;
use Ramsey\Uuid\UuidInterface;

class ConvertingPurchaseProcessRepository implements PurchaseProcessRepositoryInterface
{
    /**
     * @var PurchaseProcessRepository
     */
    private $repository;

    /**
     * @var SessionVersionConverter
     */
    private $converter;

    /**
     * ConvertingSessionRepository constructor.
     * @param PurchaseProcessRepositoryInterface $repository Session Repository
     * @param SessionVersionConverter            $converter  Session Version Converter
     */
    public function __construct(PurchaseProcessRepositoryInterface $repository, SessionVersionConverter $converter)
    {
        $this->repository = $repository;
        $this->converter  = $converter;
    }

    /**
     * @param UuidInterface $sessionId Session Id
     * @return null|SessionInfo
     * @throws \Doctrine\DBAL\DBALException
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\SessionConversionException
     */
    public function findOne(UuidInterface $sessionId): ?SessionInfo
    {
        $sessionInfo = $this->repository->findOne($sessionId);

        //TODO catch any doctrine exceptions and throw specialized domain exceptions instead
        if (!$sessionInfo instanceof SessionInfo) {
            return null;
        }

        $sessionPayload = json_decode($sessionInfo->payload(), true);
        $sessionInfo->setPayload($this->converter->convert($sessionPayload));

        return $sessionInfo;
    }

    /**
     * @param SessionInfo $session Session
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function create(SessionInfo $session): bool
    {
        //TODO catch any doctrine exceptions and throw specialized domain exceptions instead
        return $this->repository->create($session);
    }

    /**
     * @param SessionInfo $session Session
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function update(SessionInfo $session): bool
    {
        //TODO catch any doctrine exceptions and throw specialized domain exceptions instead
        return $this->repository->update($session);
    }

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
    ): array {
        $sessionsInfo = $this->repository->retrieveSessionsBetween($anEventDate, $endEventDate, $batchSize);

        $convertedSessionInfo = [];
        /**
         * @var SessionInfo $sessionInfo
         */
        foreach ($sessionsInfo as $sessionInfo) {
            $sessionPayload = json_decode($sessionInfo->payload(), true);
            $sessionInfo->setPayload($this->converter->convert($sessionPayload));
            $convertedSessionInfo[] = $sessionInfo;
        }

        return $convertedSessionInfo;
    }
}
