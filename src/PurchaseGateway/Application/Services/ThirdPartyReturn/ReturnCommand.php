<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyReturn;

use ProBillerNG\Base\Application\Services\Command;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;

class ReturnCommand extends Command
{
    /**
     * @var array
     */
    private $payload;

    /**
     * @var SessionId
     */
    private $sessionId;

    /**
     * ReturnCommand constructor.
     * @param array  $payload   Payload
     * @param string $sessionId SessionId
     * @throws \Exception
     */
    public function __construct(array $payload, string $sessionId)
    {
        $this->payload = $payload;
        $this->initSessionId($sessionId);
    }

    /**
     * @return array
     */
    public function payload(): array
    {
        return $this->payload;
    }

    /**
     * @return SessionId
     */
    public function sessionId(): SessionId
    {
        return $this->sessionId;
    }

    /**
     * @param string $sessionId Session Id
     * @return void
     * @throws \Exception
     */
    private function initSessionId(string $sessionId): void
    {
        $this->sessionId = SessionId::createFromString($sessionId);
    }

    /**
     * @return string
     */
    public function transactionId(): string
    {
        return $this->payload['ngTransactionId'] ?? $this->payload['Order'] ?? '';
    }
}
