<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\Authenticate;

interface AuthenticateThreeDDTOAssembler
{
    /**
     * @param string $acs       Acs
     * @param string $pareq     Pareq
     * @param string $sessionId Session Id
     * @return AuthenticateThreeDHttpDTO
     */
    public function assemble(string $acs, string $pareq, string $sessionId): AuthenticateThreeDHttpDTO;
}
