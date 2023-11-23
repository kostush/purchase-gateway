<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO;

interface IntegrationEventDTOAssembler
{
    /**
     * @param array $integrationEvents object
     * @return mixed
     */
    public function assemble(array $integrationEvents);
}
