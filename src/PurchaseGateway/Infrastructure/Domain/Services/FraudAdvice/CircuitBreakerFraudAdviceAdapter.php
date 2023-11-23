<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice;

use Odesk\Phystrix\CommandFactory;
use ProBillerNG\CircuitBreaker\CircuitBreaker;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudAdapter;

class CircuitBreakerFraudAdviceAdapter extends CircuitBreaker implements FraudAdapter
{
    /**
     * @var FraudAdviceAdapter
     */
    private $adapter;

    /**
     * CircuitBreakerFraudAdviceAdapter constructor.
     * @param CommandFactory     $commandFactory Command
     * @param FraudAdviceAdapter $adapter        Adapter
     */
    public function __construct(
        CommandFactory $commandFactory,
        FraudAdviceAdapter $adapter
    ) {
        parent::__construct($commandFactory);
        $this->adapter = $adapter;
    }

    /**
     * @param SiteId         $siteId    Site Id
     * @param array          $params    Params
     * @param string         $for       For
     * @param SessionId|null $sessionId Session Id
     * @return FraudAdvice
     */
    public function retrieveAdvice(SiteId $siteId, array $params, string $for, SessionId $sessionId = null): FraudAdvice
    {
        $command = $this->commandFactory->getCommand(
            RetrieveFraudAdviceCommand::class,
            $this->adapter,
            $siteId,
            $params,
            $for,
            $sessionId
        );

        return $command->execute();
    }
}
