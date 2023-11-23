<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services;

use ProBillerNG\PurchaseGateway\Infrastructure\Application\JsonWebToken;

interface TokenDecoder
{

    /**
     * @param string $token Token
     *
     * @return JsonWebToken
     */
    public function decode(string $token): JsonWebToken;
}
