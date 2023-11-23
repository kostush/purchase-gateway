<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService;

use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;

class SendEmailResponse
{
    /** @var SessionId */
    private $sessionId;

    /** @var string */
    private $traceId;

    /**
     * SendEmailResponse constructor.
     * @param SessionId $sessionId SessionId
     * @param string    $traceId   TraceId
     */
    public function __construct(SessionId $sessionId, string $traceId)
    {
        $this->sessionId = $sessionId;
        $this->traceId   = $traceId;
    }

    /**
     * @param SessionId $sessionId SessionId
     * @param string    $traceId   TraceId
     * @return SendEmailResponse
     *
     */
    public static function create(SessionId $sessionId, string $traceId): self
    {
        return new self($sessionId, $traceId);
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
    public function traceId(): string
    {
        return $this->traceId;
    }
}
