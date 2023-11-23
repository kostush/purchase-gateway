<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

interface InMemoryRepository
{
    /**
     * @param string $sessionId Session id
     * @return bool
     */
    public function storeSessionId(string $sessionId): bool;

    /**
     * @param string $sessionId Session id
     *
     * @return int
     */
    public function deleteSessionId(string $sessionId): int;

    /**
     * @param string $sessionId Session id
     * @param string $status    Status of the purchase
     *
     * @return bool
     */
    public function storePurchaseStatus(string $sessionId, string $status): bool;

    /**
     * @param string $sessionId Session id
     *
     * @return string
     */
    public function retrievePurchaseStatus(string $sessionId): string;

    /**
     * @param string $sessionId Session id
     *
     * @return int
     */
    public function deletePurchaseStatus(string $sessionId): int;

    /**
     * @param string $sessionId           Session id.
     * @param int    $gatewaySubmitNumber Gateway submit number.
     * @return bool
     */
    public function storeGatewaySubmitNumber(string $sessionId, int $gatewaySubmitNumber): bool;

    /**
     * @param string $sessionId Session id.
     *
     * @return string
     */
    public function retrieveGatewaySubmitNumber(string $sessionId): string;

    /**
     * @param string $sessionId Session id.
     *
     * @return int
     */
    public function deleteGatewaySubmitNumber(string $sessionId): int;
}
