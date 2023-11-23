<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\TokenInterface;

/**
 * Interface TokenGenerator
 * Token generated to be returned along with response
 *
 * @package ProBillerNG\PurchaseGateway\Application\Services
 */
interface TokenGenerator
{
    /**
     * @param Site  $site           Site
     * @param int   $publicKeyIndex Public Key Index
     * @param array $tokenPayload   Token Payload
     * @return TokenInterface
     */
    public function generateWithPublicKey(Site $site, int $publicKeyIndex, array $tokenPayload = []): TokenInterface;

    /**
     * @param Site  $site         Site
     * @param array $tokenPayload Token Payload
     * @return TokenInterface
     */
    public function generateWithPrivateKey(Site $site, array $tokenPayload = []): TokenInterface;

    /**
     * @param array       $tokenPayload Token Payload
     * @param string|null $key          Key
     * @return TokenInterface
     */
    public function generateWithGenericKey(array $tokenPayload = [], ?string $key = null): TokenInterface;
}
