<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyPostback;

use Exception;
use ProBillerNG\Base\Application\Services\Command;
use ProBillerNG\PurchaseGateway\Application\Exceptions\NoBodyOrHeaderReceivedException;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;

class ThirdPartyPostbackCommand extends Command
{
    /**
     * @var SessionId
     */
    private $sessionId;

    /**
     * @var array
     */
    private $payload;

    /**
     * @var string
     */
    private $type;

    /**
     * PostbackCommand constructor.
     * @param string $sessionId Purchase gateway session id.
     * @param array  $payload   Payload.
     * @param string $type      Postback command type.
     * @throws NoBodyOrHeaderReceivedException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function __construct(string $sessionId, array $payload, string $type)
    {
        $this->initPayload($payload);
        $this->initSessionId($sessionId);
        $this->type = $type;
    }

    /**
     * @return SessionId
     */
    public function sessionId(): SessionId
    {
        return $this->sessionId;
    }

    /**
     * @return array
     */
    public function payload(): array
    {
        return $this->payload;
    }

    /**
     * @return string
     */
    public function transactionId(): string
    {
        return $this->payload['ngTransactionId'] ?? $this->payload['trans_order'] ?? '';
    }

    /**
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * @param string $sessionId Session id.
     * @return void
     * @throws Exception
     */
    private function initSessionId(string $sessionId): void
    {
        $this->sessionId = SessionId::createFromString($sessionId);
    }

    /**
     * @param array $payload Payload
     * @return void
     * @throws NoBodyOrHeaderReceivedException
     * @throws \ProBillerNG\Logger\Exception
     */
    private function initPayload(array $payload): void
    {
        if (empty($payload)) {
            throw new NoBodyOrHeaderReceivedException();
        }

        $this->payload = $payload;
    }
}
