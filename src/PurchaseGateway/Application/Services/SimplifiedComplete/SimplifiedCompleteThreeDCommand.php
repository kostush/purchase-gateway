<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\SimplifiedComplete;

use Exception;
use ProBillerNG\Base\Application\Services\Command;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;

class SimplifiedCompleteThreeDCommand extends Command
{
    /**
     * @var array
     */
    private $queryString;

    /**
     * @var SessionId
     */
    private $sessionId;

    /**
     * SimplifiedCompleteThreeDCommand constructor.
     * @param string $sessionId   Session id.
     * @param array  $queryString Query string.
     * @throws Exception
     */
    public function __construct(
        string $sessionId,
        array $queryString
    ) {
        $this->initSessionId($sessionId);
        $this->queryString = $queryString;
    }

    /**
     * @return SessionId
     */
    public function sessionId(): SessionId
    {
        return $this->sessionId;
    }

    /**
     * @return string
     */
    public function queryString(): string
    {
        return http_build_query($this->queryString);
    }

    /**
     * @return string
     */
    public function invoiceId(): string
    {
        if (!array_key_exists('invoiceID', $this->queryString)) {
            return '';
        }

        return $this->queryString['invoiceID'];
    }

    /**
     * @return string
     */
    public function hash(): string
    {
        if (!array_key_exists('hash', $this->queryString)) {
            return '';
        }

        return $this->queryString['hash'];
    }

    /**
     * @param string $sessionId Session Id
     * @return void
     * @throws Exception
     */
    private function initSessionId(string $sessionId): void
    {
        $this->sessionId = SessionId::createFromString($sessionId);
    }
}
