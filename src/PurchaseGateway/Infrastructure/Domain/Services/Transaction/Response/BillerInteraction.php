<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

interface BillerInteraction
{
    /**
     * @return string
     */
    public function transactionId(): string;

    /**
     * @return string
     */
    public function status(): string;

    /**
     * @return string
     */
    public function paymentType(): string;

    /**
     * @return string
     */
    public function paymentMethod(): string;
}
