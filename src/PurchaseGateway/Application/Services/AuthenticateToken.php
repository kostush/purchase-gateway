<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\Site;

/**
 * Interface AuthenticateToken
 * Authenticate request using Token
 *
 * @package ProBillerNG\PurchaseGateway\Application\Services
 */
interface AuthenticateToken
{
    /**
     * Responsible for token authentication
     *
     * @param string $token Token
     * @param Site   $site  Site
     * @return bool
     */
    public function authenticate(string $token, Site $site): bool;
}
