<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Repository\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use ProBillerNG\PurchaseGateway\Domain\Model\Session\SessionInfo;
use ProBillerNG\PurchaseGateway\Domain\Repository\PurchaseProcessRepositoryInterface;
use Ramsey\Uuid\UuidInterface;

class PurchaseProcessRepository implements PurchaseProcessRepositoryInterface
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * SessionRepository constructor.
     * @param EntityManagerInterface $entityManager The entity manager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param UuidInterface $sessionId The uuid object
     * @return null|SessionInfo
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function findOne(UuidInterface $sessionId): ?SessionInfo
    {
        $stmt = $this->entityManager->getConnection()
            ->prepare('select * from `sessions` where `id` = :id');

        $stmt->bindValue(':id', $sessionId->toString());
        if ($stmt->execute()) {
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!empty($result)) {
                return SessionInfo::create(
                    $result['id'],
                    $result['payload'],
                    new \DateTime($result['created_at'])
                );
            }
        }

        return null;
    }

    /**
     * @param SessionInfo $session Session object
     * @return mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    public function create(SessionInfo $session): bool
    {
        return $this->entityManager->getConnection()
            ->prepare(
                'insert into `sessions` (`id`, `payload`, `created_at`) values (:id, :payload, :created)'
            )->execute(
                [
                    'id'      => $session->id(),
                    'payload' => $session->payload(),
                    'created' => $session->createdAt()->format('Y-m-d H:i:s')
                ]
            );
    }

    /**
     * @param SessionInfo $session Session object
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function update(SessionInfo $session): bool
    {
        return $this->entityManager->getConnection()
            ->prepare(
                'update `sessions` set `payload` = :payload where `id` = :id'
            )->execute(
                [
                    'id'      => $session->id(),
                    'payload' => $session->payload()
                ]
            );
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

        $select = 'select * from `sessions` where';

        if ($anEventDate) {
            $select .= ' `created_at` > :startDate and';
        }

        $select .= ' `created_at` <= :endDate order by `created_at` limit ' . $batchSize;


        $stmt = $this->entityManager->getConnection()
            ->prepare($select);

        if ($anEventDate) {
            $stmt->bindValue(':startDate', $anEventDate->format('Y-m-d H:i:s'));
        }
        $stmt->bindValue(':endDate', $endEventDate->format('Y-m-d H:i:s'));

        $sessions = [];

        if ($stmt->execute()) {
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($result)) {
                return [];
            }

            foreach ($result as $session) {
                $sessions[] = SessionInfo::create(
                    $session['id'],
                    $session['payload'],
                    new \DateTime($session['created_at'])
                );
            }
        }

        return $sessions;
    }
}
