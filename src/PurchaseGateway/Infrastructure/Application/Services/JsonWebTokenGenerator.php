<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Application\Services;

use DateInterval;
use Lcobucci\JWT\Parsing\Encoder;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Hmac\Sha512;
use Lcobucci\JWT\Token;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\TokenInterface;
use Lcobucci\JWT\Builder as TokenBuilder;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\JsonWebToken;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;

class JsonWebTokenGenerator implements TokenGenerator
{
    public const TOKEN_TTL = 1800;

    /**
     * @param Site  $site           Site
     * @param int   $publicKeyIndex Public Key Index
     * @param array $tokenPayload   Token Payload
     *
     * @return TokenInterface
     * @throws \Exception
     */
    public function generateWithPublicKey(Site $site, int $publicKeyIndex, array $tokenPayload = []): TokenInterface
    {
        $tokenBuilder = $this->generateTokenBuilder($tokenPayload);

        $configuration = $this->getConfiguration($site->publicKeys()[$publicKeyIndex]);

        // generate token
        return $this->convertToken($tokenBuilder->getToken($configuration->signer(), new Signer\Key($site->publicKeys()[$publicKeyIndex])));
    }

    /**
     * @param Site  $site         Site
     * @param array $tokenPayload Token Payload
     *
     * @return TokenInterface
     * @throws \Exception
     */
    public function generateWithPrivateKey(Site $site, array $tokenPayload = []): TokenInterface
    {
        $tokenBuilder = $this->generateTokenBuilder($tokenPayload);

        $configuration = $this->getConfiguration($site->privateKey());

        // generate token
        return $this->convertToken($tokenBuilder->getToken($configuration->signer(), new Signer\Key($site->privateKey())));
    }

    /**
     * @param array       $tokenPayload Token Payload
     * @param string|null $key          Key
     *
     * @return TokenInterface
     * @throws \Exception
     */
    public function generateWithGenericKey(array $tokenPayload = [], ?string $key = null): TokenInterface
    {
        $tokenBuilder = $this->generateTokenBuilder($tokenPayload);

        $signingKey   =  $key ?? env('APP_JWT_KEY');

        $configuration = $this->getConfiguration($signingKey);

        // generate token
        return $this->convertToken($tokenBuilder->getToken($configuration->signer(), new Signer\Key($signingKey)));
    }

    /**
     * @param array $tokenPayload Token Payload
     *
     * @return TokenBuilder
     * @throws \Exception
     */
    private function generateTokenBuilder(array $tokenPayload): TokenBuilder
    {
        $now = new \DateTimeImmutable();
        $dateTimeImmutableClonedValue  = clone $now;
        $dateTimeImmutableClonedValue  = $dateTimeImmutableClonedValue->add(new DateInterval('PT' . self::TOKEN_TTL . 'S'));
        // set standard claims
        $tokenBuilder = (new TokenBuilder())
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($dateTimeImmutableClonedValue);

        // set custom claims
        foreach ($tokenPayload as $claim => $value) {
            $tokenBuilder->set($claim, $value);
        }

        return $tokenBuilder;
    }

    /**
     * Maps JWT token from external library to the one
     *
     * @param Token $token JWT token
     *
     * @return JsonWebToken
     */
    public function convertToken(Token $token): JsonWebToken
    {

        $signature = $token->signature();
        $payload   = explode('.', $token->payload());
        $payload[] = (new Encoder())->base64UrlEncode($signature);

        return new JsonWebToken($token->getHeaders(), $token->getClaims(), $signature, $payload);
    }

    /**
     * Returns the JWT signing strategy
     *
     * @return Sha512
     */
    public function getSigner()
    {
        return new Sha512();
    }

    /**
     * @param $key
     *
     * @return Configuration
     */
    public function getConfiguration($key): Configuration
    {
        // set signing info
        $configuration = Configuration::forSymmetricSigner(

            // You may use any HMAC variations (256, 384, and 512)
            $this->getSigner(),

            // replace the value below with a key of your own!
            InMemory::base64Encoded(
                base64_encode($key)
            )
        );

        return $configuration;
    }
}
