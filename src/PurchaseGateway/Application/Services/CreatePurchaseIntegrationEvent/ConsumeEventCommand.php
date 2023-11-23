<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent;

use ProBillerNG\Base\Application\Services\Command;

class ConsumeEventCommand extends Command
{
    /**
     * @var string
     */
    private $eventBody;

    /**
     * ConsumeEventCommand constructor.
     * @param string $eventBody Event body json
     */
    private function __construct(string $eventBody)
    {
        $this->eventBody = $eventBody;
    }

    /**
     * @param string $eventBody Event body json
     * @return ConsumeEventCommand
     */
    public static function create(string $eventBody): self
    {
        return new static($eventBody);
    }

    /**
     * @return string
     */
    public function eventBody(): string
    {
        return $this->eventBody;
    }
}
