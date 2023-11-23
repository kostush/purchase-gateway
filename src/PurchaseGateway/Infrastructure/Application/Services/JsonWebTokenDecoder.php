<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Application\Services;

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Parsing\Encoder;
use ProBillerNG\PurchaseGateway\Application\Services\TokenDecoder;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\JsonWebToken;

class JsonWebTokenDecoder implements TokenDecoder
{
    /**
     * @param string $token
     *
     * @return JsonWebToken
     */
    public function decode(string $token): JsonWebToken
    {
        $jwt = (new Parser())->parse($token);

        $signature = null;
        $payload   = explode('.', $jwt->getPayload());
        $payload[] = (new Encoder())->base64UrlEncode($signature);

        return new JsonWebToken($jwt->getHeaders(), $jwt->getClaims(), $signature, $payload);
    }
}
