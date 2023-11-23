<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\PurchaseGatewayHealth;

class HttpQueryHealthDTOAssembler implements PurchaseGatewayHealthDTOAssembler
{
    /**
     * @param array $health Health array.
     * @return mixed|PurchaseGatewayHealthHttpDTO
     */
    public function assemble(array $health)
    {
        return PurchaseGatewayHealthHttpDTO::create($health);
    }
}
