<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation;

use Odesk\Phystrix\CommandFactory;
use ProBillerNG\CircuitBreaker\CircuitBreaker;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendationCollection;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\FraudRecommendationAdapter;

class CircuitBreakerFraudRecommendation extends CircuitBreaker implements FraudRecommendationAdapter
{
    /**
     * @var FraudRecommendationAdapter
     */
    private $adapter;

    /**
     * CircuitBreakerFraudAdviceAdapter constructor.
     * @param CommandFactory             $commandFactory Command
     * @param FraudRecommendationAdapter $adapter        Adapter
     */
    public function __construct(
        CommandFactory $commandFactory,
        FraudRecommendationAdapter $adapter
    ) {
        parent::__construct($commandFactory);
        $this->adapter = $adapter;
    }

    /**
     * @param string $businessGroupId
     * @param string $siteId
     * @param string $event
     * @param array  $data
     * @param string $sessionId
     * @param array  $fraudHeaders
     *
     * @return FraudRecommendationCollection
     */
    public function retrieve(
        string $businessGroupId,
        string $siteId,
        string $event,
        array $data,
        string $sessionId,
        array $fraudHeaders
    ): FraudRecommendationCollection {
        $command = $this->commandFactory->getCommand(
            RetrieveFraudRecommendationCommand::class,
            $this->adapter,
            $businessGroupId,
            $siteId,
            $event,
            $data,
            $sessionId,
            $fraudHeaders
        );

        return $command->execute();
    }
}
