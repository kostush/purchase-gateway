<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain;

interface TokenInterface
{
    /**
     * @return string
     */
    public function getPayload();

    /**
     * @return string
     */
    public function __toString();
}
