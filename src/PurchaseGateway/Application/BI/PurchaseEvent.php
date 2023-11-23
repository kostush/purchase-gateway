<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\BI;

use Illuminate\Contracts\Support\Arrayable;
use ProBillerNG\BI\Event\BaseEvent;

class PurchaseEvent extends BaseEvent implements Arrayable
{
    const LATEST_VERSION = 2;

    /**
     * @var string
     */
    protected $sessionId;

    /**
     * @var string
     */
    protected $siteId;

    /**
     * @var string
     */
    protected $timestamp;

    /**
     * @var int
     */
    protected $version;

    /**
     * PurchaseEvent constructor.
     *
     * @param string                  $type      Event Type.
     * @param string                  $sessionId Session Id
     * @param string                  $siteId    Site Id
     * @param \DateTimeImmutable|null $timestamp Timestamp of Occurred Event
     */
    public function __construct(
        string $type,
        string $sessionId,
        string $siteId,
        ?\DateTimeImmutable $timestamp
    ) {

        parent::__construct($type);
        $this->sessionId = $sessionId;
        $this->siteId    = $siteId;
        $this->timestamp = $timestamp->format('Y-m-d H:i:s');
        $this->version   = static::LATEST_VERSION;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return get_object_vars($this);
    }
}
