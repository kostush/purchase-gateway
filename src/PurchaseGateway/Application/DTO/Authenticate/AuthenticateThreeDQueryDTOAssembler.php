<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\Authenticate;

use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;

class AuthenticateThreeDQueryDTOAssembler implements AuthenticateThreeDDTOAssembler
{
    /**
     * @var CryptService
     */
    private $cryptService;

    /**
     * @var TokenGenerator
     */
    private $tokenGenerator;

    /**
     * AuthenticateThreeDFactory constructor.
     * @param CryptService   $cryptService   Crypt Service
     * @param TokenGenerator $tokenGenerator Token Generator
     */
    public function __construct(CryptService $cryptService, TokenGenerator $tokenGenerator)
    {
        $this->cryptService   = $cryptService;
        $this->tokenGenerator = $tokenGenerator;
    }

    /**
     * @param string $acs       Acs
     * @param string $pareq     Pareq
     * @param string $sessionId Session Id
     * @return AuthenticateThreeDHttpDTO
     */
    public function assemble(string $acs, string $pareq, string $sessionId): AuthenticateThreeDHttpDTO
    {
        return AuthenticateThreeDHttpDTO::create(
            $this->cryptService,
            $this->tokenGenerator,
            $acs,
            $pareq,
            $sessionId
        );
    }
}
