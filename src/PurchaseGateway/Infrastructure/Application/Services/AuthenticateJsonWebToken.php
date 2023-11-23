<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Application\Services;

use Lcobucci\JWT\Signer\Key\InMemory;
use ProBillerNG\PurchaseGateway\Application\Exceptions\TokenIsExpiredException;
use ProBillerNG\PurchaseGateway\Application\Services\AuthenticateToken;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use Lcobucci\JWT\Parser;

class AuthenticateJsonWebToken implements AuthenticateToken
{
    /**
     * @var JsonWebTokenGenerator
     */
    protected $tokenGenerator;

    /**
     * AuthenticateKeyTranslatingService constructor.
     * @param JsonWebTokenGenerator $tokenGenerator Json Web TokenGenerator
     */
    public function __construct(JsonWebTokenGenerator $tokenGenerator)
    {
        $this->tokenGenerator = $tokenGenerator;
    }

    /**
     * @param string $token Token
     * @param Site   $site  Site
     * @return bool
     * @throws TokenIsExpiredException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function authenticate(string $token, Site $site): bool
    {
        $jwt = (new Parser())->parse($token);

        $now = new \DateTimeImmutable();

        if ($jwt->isExpired($now)) {
            throw new TokenIsExpiredException();
        }

        return $jwt->verify(
            $this->tokenGenerator->getSigner(),
            InMemory::base64Encoded(base64_encode($site->privateKey()))
        );
    }
}
