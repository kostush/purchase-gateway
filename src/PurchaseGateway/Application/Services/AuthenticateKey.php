<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\Site;

/**
 * Interface AuthenticateKey
 * Authenticate request using API key
 *
 * @package ProBillerNG\PurchaseGateway\Application\Services
 */
interface AuthenticateKey
{
    /**
     * @param Site   $site      Site
     * @param string $publicKey Public Key
     * @return int|null
     */
    public function getPublicKeyIndex(Site $site, string $publicKey): ?int;
}
