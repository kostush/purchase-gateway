<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\Complete;

use ProBillerNG\Base\Application\Services\Command;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;

class CompleteThreeDCommand extends Command
{
    /**
     * @var string|null
     */
    private $pares;

    /**
     * @var string|null
     */
    private $md;

    /**
     * @var SessionId
     */
    private $sessionId;

    /**
     * CompleteThreeDCommand constructor.
     * @param string      $sessionId Session id.
     * @param string|null $pares     Pares.
     * @param string|null $md        Rocketgate biller transaction id.
     * @throws \Exception
     */
    public function __construct(
        string $sessionId,
        ?string $pares,
        ?string $md
    ) {
        $this->initPares($pares);
        $this->initMD($md);
        $this->initSessionId($sessionId);
    }

    /**
     * @return SessionId
     */
    public function sessionId(): SessionId
    {
        return $this->sessionId;
    }

    /**
     * @return string|null
     */
    public function pares(): ?string
    {
        return $this->pares;
    }

    /**
     * @return string|null
     */
    public function md(): ?string
    {
        return $this->md;
    }

    /**
     * @param string|null $md MD
     * @return void
     */
    public function initMD(?string $md): void
    {
        $this->md = $md;

        if (empty($md) || $md === "null") {
            $this->md = null;
        }
    }

    /**
     * @param string|null $pares Pares
     * @return void
     */
    public function initPares(?string $pares): void
    {
        $this->pares = $pares;

        if (empty($pares) || $pares === 'null') {
            $this->pares = null;
        }
    }

    /**
     * @param string $sessionId Session Id
     * @return void
     * @throws \Exception
     */
    public function initSessionId(string $sessionId): void
    {
        $this->sessionId = SessionId::createFromString($sessionId);
    }
}
