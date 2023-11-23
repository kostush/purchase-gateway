<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Repository\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Repository\PostbackJobsRepositoryInterface;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Exception\RepositoryException;

class PostbackJobsRepository implements PostbackJobsRepositoryInterface
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * SessionRepository constructor.
     * @param EntityManagerInterface $entityManager The Entity manager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->connection    = $this->entityManager->getConnection();
    }

    /**
     * Retrieve number of failed jobs
     * @throws RepositoryException
     * @return int
     * @throws \ProBillerNG\Logger\Exception
     */
    public function getNumberOfFailedPostbackJobs(): int
    {
        //TODO use APC for caching
        try {
            $res = $this->connection->fetchAssoc('select count(*) as failed_jobs_count from failed_jobs');
            return (int) $res['failed_jobs_count'];
        } catch (\Throwable $e) {
            throw new RepositoryException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Retrieve number of jobs in postback queue
     * @throws RepositoryException
     * @return int
     * @throws \ProBillerNG\Logger\Exception
     */
    public function getQueueLengthOfPostbackJobs(): int
    {
        //TODO use APC for caching
        try {
            $res = $this->connection->fetchAssoc('select count(*) as job_count from jobs');
            return (int) $res['job_count'];
        } catch (\Throwable $e) {
            throw new RepositoryException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
