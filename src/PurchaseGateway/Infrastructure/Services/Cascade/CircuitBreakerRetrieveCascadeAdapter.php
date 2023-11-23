<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Services\Cascade;

use Odesk\Phystrix\CommandFactory;
use ProBillerNG\CircuitBreaker\CircuitBreaker;
use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;

class CircuitBreakerRetrieveCascadeAdapter extends CircuitBreaker implements CascadeAdapter
{
    /**
     * @var CascadeAdapter
     */
    private $adapter;

    /**
     * CircuitBreakerCascadeAdapter constructor.
     * @param CommandFactory $commandFactory Command
     * @param CascadeAdapter $adapter        Adapter
     */
    public function __construct(
        CommandFactory $commandFactory,
        CascadeAdapter $adapter
    ) {
        parent::__construct($commandFactory);
        $this->adapter = $adapter;
    }

    /**
     * @param string      $sessionId       Session Id
     * @param string      $siteId          Site Id
     * @param string      $businessGroupId Business Group Id
     * @param string      $country         Country
     * @param string      $paymentType     Payment type
     * @param string|null $paymentMethod   Payment method
     * @param string|null $trafficSource   Traffic source
     * @return Cascade
     */
    public function get(
        string $sessionId,
        string $siteId,
        string $businessGroupId,
        string $country,
        string $paymentType,
        ?string $paymentMethod,
        ?string $trafficSource
    ): Cascade {
        $command = $this->commandFactory->getCommand(
            RetrieveCascadeCommand::class,
            $this->adapter,
            $sessionId,
            $siteId,
            $businessGroupId,
            $country,
            $paymentType,
            $paymentMethod,
            $trafficSource
        );

        return $command->execute();
    }
}
