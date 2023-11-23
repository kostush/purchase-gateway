<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

interface BillerAvailablePaymentMethods
{
    /**
     * @return array
     */
    public function availablePaymentMethods(): array;

    /**
     * @param string|null $paymentMethod Payment method
     * @return void
     */
    public function addPaymentMethod(?string $paymentMethod): void;
}
