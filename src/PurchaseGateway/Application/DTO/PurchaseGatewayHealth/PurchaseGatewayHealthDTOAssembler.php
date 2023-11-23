<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\PurchaseGatewayHealth;

interface PurchaseGatewayHealthDTOAssembler
{
    /**
     * Assemble purchase gateway health response
     *
     * @param array $health Health array.
     * @return mixed
     */
    public function assemble(array $health);
}
