<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\BI;

use ProBillerNG\BI\Event\BaseEvent;

class PurchaseRedirectedTo3DAuthentication extends BaseEvent
{
    const TYPE = 'Purchase_Redirected_To_3D_Authentication';

    const LATEST_VERSION = 1;

    /**
     * @var string
     */
    private $status;

    /**
     * @var string
     */
    private $sessionId;

    /**
     * @var string
     */
    private $timestamp;

    /**
     * PurchaseRedirectedTo3DAuthentication constructor.
     *
     * @param string $sessionId Session Id
     * @param string $status    Purchase State
     * @throws \Exception
     */
    public function __construct(
        string $sessionId,
        string $status
    ) {
        parent::__construct(self::TYPE);

        $this->timestamp = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->sessionId = $sessionId;
        $this->status    = $status;

        $this->setValue($this->toArray());
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'type'      => self::TYPE,
            'version'   => self::LATEST_VERSION,
            'timestamp' => $this->timestamp,
            'sessionId' => $this->sessionId,
            'status'    => $this->status,
        ];
    }
}
