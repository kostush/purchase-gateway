<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services;

/**
 * Interface AuthenticateToken
 * Authenticate request using Token
 *
 * @package ProBillerNG\PurchaseGateway\Application\Services
 */
interface SessionToken
{
    /**
     * Responsible for token authentication using a generic key
     *
     * @param string      $token Token
     * @param string|null $key   Key
     * @return void
     */
    public function decodeToken(string $token, ?string $key = null): void;

    /**
     * @return bool
     */
    public function checkIsExpired();

    /**
     * @return bool
     */
    public function isValid();

    /**
     * @return string
     */
    public function sessionId(): string;
}
