<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\Authenticate;

use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;

class AuthenticateThreeDHttpDTO implements \JsonSerializable
{
    /**
     * @var array
     */
    private $response;

    /**
     * @var TokenGenerator
     */
    private $tokenGenerator;

    /** @var CryptService */
    private $cryptService;

    /**
     * AuthenticateThreeDHttpDTO constructor.
     * @param CryptService   $cryptService   Crypt Service
     * @param TokenGenerator $tokenGenerator Token Generator
     * @param string         $acs            Acs
     * @param string         $pareq          Pareq
     * @param string         $sessionId      Session Id
     */
    private function __construct(
        CryptService $cryptService,
        TokenGenerator $tokenGenerator,
        string $acs,
        string $pareq,
        string $sessionId
    ) {
        $this->cryptService   = $cryptService;
        $this->tokenGenerator = $tokenGenerator;

        $this->response['acs']   = $acs;
        $this->response['pareq'] = $pareq;
        $this->response['jwt']   = $this->createReturnUrl($sessionId);
    }

    /**
     * @param CryptService   $cryptService   Crypt Service
     * @param TokenGenerator $tokenGenerator Token Generator
     * @param string         $acs            Acs
     * @param string         $pareq          Pareq
     * @param string         $sessionId      Session Id
     * @return AuthenticateThreeDHttpDTO
     */
    public static function create(
        CryptService $cryptService,
        TokenGenerator $tokenGenerator,
        string $acs,
        string $pareq,
        string $sessionId
    ) {
        return new self(
            $cryptService,
            $tokenGenerator,
            $acs,
            $pareq,
            $sessionId
        );
    }

    /**
     * @param string $sessionId Session Id
     * @return string
     */
    private function createReturnUrl(string $sessionId): string
    {
        $jwt = (string) $this->tokenGenerator->generateWithGenericKey(
            [
                'sessionId' => $this->cryptService->encrypt($sessionId)
            ]
        );

        return $jwt;
    }

    /**
     * @return array|string
     */
    public function __toString()
    {
        return json_encode($this->response);
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->response;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->response;
    }
}
