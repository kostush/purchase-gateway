<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Repository;

use ProBillerNG\PurchaseGateway\Domain\FailedEventPublish;

interface FailedEventPublishRepository
{
    /**
     * @return mixed
     */
    public function findBatch();

    /**
     * @param FailedEventPublish $failedEventPublish Entity
     * @return void
     */
    public function add(FailedEventPublish $failedEventPublish): void;

    /**
     * @param FailedEventPublish $failedEventPublish Entity
     * @return void
     */
    public function update(FailedEventPublish $failedEventPublish): void;
}
