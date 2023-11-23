<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Application\Services;

use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token;
use ProBillerNG\PurchaseGateway\Application\Services\SessionToken;
use Lcobucci\JWT\Parser;

class SessionWebToken implements SessionToken
{
    public const SESSION_KEY = 'sessionId';

    /**
     * @var JsonWebTokenGenerator
     */
    protected $tokenGenerator;

    /**
     * @var Token
     */
    protected $jwt;

    /**
     * @var string
     */
    protected $key;

    /**
     * SessionWebToken constructor.
     * @param JsonWebTokenGenerator $tokenGenerator Json Web TokenGenerator
     */
    public function __construct(JsonWebTokenGenerator $tokenGenerator)
    {
        $this->tokenGenerator = $tokenGenerator;
    }

    /**
     * @param string      $token Token
     * @param string|null $key   Key
     * @return void
     */
    public function decodeToken(string $token, ?string $key = null): void
    {
        $this->jwt = (new Parser())->parse($token);
        $this->key = $key ?? env('APP_JWT_KEY');
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function checkIsExpired()
    {
        $now = new \DateTimeImmutable();
        return $this->jwt->isExpired($now);
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        $valid = $this->jwt->verify(
            $this->tokenGenerator->getSigner(),
            InMemory::base64Encoded(base64_encode($this->key))
        );

        return $valid;
    }

    /**
     * @return string
     */
    public function sessionId(): string
    {
        return $this->jwt->getClaim(self::SESSION_KEY);
    }
}
