<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState;

interface State
{
    /**
     * @return bool
     */
    public function created(): bool;

    /**
     * @return bool
     */
    public function blockedDueToFraudAdvice(): bool;

    /**
     * @return bool
     */
    public function valid(): bool;

    /**
     * @return bool
     */
    public function processing(): bool;

    /**
     * @return bool
     */
    public function processed(): bool;

    /**
     * @return bool
     */
    public function cascadeBillersExhausted(): bool;

    /**
     * @return bool
     */
    public function pending(): bool;

    /**
     * @return bool
     */
    public function threeDAuthenticated(): bool;

    /**
     * @return bool
     */
    public function threeDLookupPerformed(): bool;
}
