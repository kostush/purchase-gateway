<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

interface PurchaseRepository
{
    /**
     * Adds a purchase
     *
     * @param Purchase $purchase Purchase
     * @return void
     */
    public function add(Purchase $purchase);

    /**
     * Finds a purchase by id
     *
     * @param PurchaseId $id Purchase id
     * @return null|Purchase
     */
    public function findById(PurchaseId $id): ?Purchase;

    /**
     * @param Purchase $purchase Purchase
     * @return void
     */
    public function addOrUpdatePurchase(Purchase $purchase): void;
}
