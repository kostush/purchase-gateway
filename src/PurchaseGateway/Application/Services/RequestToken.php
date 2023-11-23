<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services;

/**
 * Interface AzureActiveDirectoryHelper
 * Receive authorization token from Azure Active Directory
 *
 * @package ProBillerNG\PurchaseGateway\Application\Services
 */
interface RequestToken
{
    /**
     * @param string $clientSecret
     * @param string $resource
     * @param bool   $skipCache
     *
     * @return string|null
     */
    public function getToken(string $clientSecret, string $resource, bool $skipCache = false): ?string;
}
