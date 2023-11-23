<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BillerMapping;

use Odesk\Phystrix\CommandFactory;
use ProBillerNG\CircuitBreaker\CircuitBreaker;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Services\BillerMappingAdapter as BillerMappingAdapterInterface;

class CircuitBreakerBillerMappingAdapter extends CircuitBreaker implements BillerMappingAdapterInterface
{
    /**
     * @var BillerMappingAdapter
     */
    private $adapter;

    /**
     * CircuitBreakerBillerMappingAdapter constructor.
     * @param CommandFactory       $commandFactory Command
     * @param BillerMappingAdapter $adapter        Adapter
     */
    public function __construct(
        CommandFactory $commandFactory,
        BillerMappingAdapter $adapter
    ) {
        parent::__construct($commandFactory);
        $this->adapter = $adapter;
    }

    /**
     * @param string $billerName      Biller Name.
     * @param string $businessGroupId Business GroupId
     * @param string $siteId          Site UUID
     * @param string $currencyCode    Currency Code
     * @param string $sessionId       Session UUID
     *
     * @return BillerMapping
     */
    public function retrieveBillerMapping(
        string $billerName,
        string $businessGroupId,
        string $siteId,
        string $currencyCode,
        string $sessionId
    ): BillerMapping {
        $command = $this->commandFactory->getCommand(
            RetrieveBillerMappingCommand::class,
            $this->adapter,
            $billerName,
            $businessGroupId,
            $siteId,
            $currencyCode,
            $sessionId
        );

        return $command->execute();
    }
}
