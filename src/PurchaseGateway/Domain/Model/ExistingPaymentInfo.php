<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

interface ExistingPaymentInfo
{
    /**
     * @return string|null
     */
    public function cardHash(): ?string;

    /**
     * @return string|null
     */
    public function paymentTemplateId(): ?string;
}
