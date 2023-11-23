<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Application;

use Lcobucci\JWT\Token;
use ProBillerNG\PurchaseGateway\Domain\TokenInterface;

class JsonWebToken extends Token implements TokenInterface
{
    public const SESSION_KEY        = 'sessionId';
    public const CORRELATION_ID_KEY = 'X-CORRELATION-ID';

    /**
     * @return string|null
     */
    public function getSessionId(): ?string
    {
        if ($this->hasClaim(self::SESSION_KEY)) {
            return $this->getClaim(self::SESSION_KEY)->getValue();
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getCorrelationId(): ?string
    {
        if ($this->hasClaim(self::CORRELATION_ID_KEY)) {
            return $this->getClaim(self::CORRELATION_ID_KEY)->getValue();
        }

        return null;
    }
}
