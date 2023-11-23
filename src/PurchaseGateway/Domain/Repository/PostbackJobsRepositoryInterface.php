<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Repository;


interface PostbackJobsRepositoryInterface
{
    /**
     * @return int
     */
    public function getNumberOfFailedPostbackJobs(): int;

    /**
     * @return int
     */
    public function getQueueLengthOfPostbackJobs(): int;
}
