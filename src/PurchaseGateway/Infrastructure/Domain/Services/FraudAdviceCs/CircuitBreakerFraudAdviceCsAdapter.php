<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs;

use Odesk\Phystrix\CommandFactory;
use ProBillerNG\CircuitBreaker\CircuitBreaker;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudCsAdapter;

/**
 * @deprecated
 * Class CircuitBreakerFraudAdviceCsAdapter
 * @package ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs
 */
class CircuitBreakerFraudAdviceCsAdapter extends CircuitBreaker implements FraudCsAdapter
{
    /**
     * @var FraudAdviceCsAdapter
     */
    private $adapter;

    /**
     * CircuitBreakerFraudAdviceAdapter constructor.
     * @param CommandFactory       $commandFactory Command
     * @param FraudAdviceCsAdapter $adapter        Adapter
     */
    public function __construct(
        CommandFactory $commandFactory,
        FraudAdviceCsAdapter $adapter
    ) {
        parent::__construct($commandFactory);
        $this->adapter = $adapter;
    }

    /**
     * @param PaymentTemplateCollection $paymentTemplateCollection Payment Template Collection
     * @param string                    $sessionId                 Session Id
     * @return void
     */
    public function retrieveAdvice(PaymentTemplateCollection $paymentTemplateCollection, string $sessionId): void
    {
        $command = $this->commandFactory->getCommand(
            RetrieveFraudAdviceCsCommand::class,
            $this->adapter,
            $paymentTemplateCollection,
            $sessionId
        );

        $command->execute();
    }
}
