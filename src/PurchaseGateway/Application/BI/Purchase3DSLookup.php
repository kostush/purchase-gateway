<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\BI;

use ProBillerNG\BI\Event\BaseEvent;

class Purchase3DSLookup extends BaseEvent
{
    const TYPE = 'Purchase_3DS_Lookup';

    const LATEST_VERSION = 1;

    /**
     * @var int
     */
    private $threedDVersion;

    /**
     * @var string
     */
    private $sessionId;

    /**
     * @var string
     */
    private $timestamp;

    /**
     * Purchase3DSLookup constructor.
     *
     * @param string $sessionId      Session Id
     * @param int    $threedDVersion Threeds version
     * @throws \Exception
     */
    public function __construct(
        string $sessionId,
        int $threedDVersion
    ) {
        parent::__construct(self::TYPE);

        $this->timestamp      = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->sessionId      = $sessionId;
        $this->threedDVersion = $threedDVersion;

        $this->setValue($this->toArray());
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'type'          => self::TYPE,
            'version'       => self::LATEST_VERSION,
            'timestamp'     => $this->timestamp,
            'sessionId'     => $this->sessionId,
            'threedVersion' => $this->threedDVersion,
        ];
    }
}
