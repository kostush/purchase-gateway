<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate;

use Odesk\Phystrix\CommandFactory;
use ProBillerNG\CircuitBreaker\CircuitBreaker;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Services\RetrievePaymentTemplateAdapter;

class CircuitBreakerRetrievePaymentTemplateServiceAdapter extends CircuitBreaker implements RetrievePaymentTemplateAdapter
{
    /**
     * @var RetrievePaymentTemplateAdapter
     */
    private $adapter;

    /**
     * CircuitBreakerRetrievePaymentTemplateServiceAdapter constructor.
     * @param CommandFactory                        $commandFactory Command
     * @param RetrievePaymentTemplateServiceAdapter $adapter        Adapter
     */
    public function __construct(
        CommandFactory $commandFactory,
        RetrievePaymentTemplateServiceAdapter $adapter
    ) {
        parent::__construct($commandFactory);
        $this->adapter = $adapter;
    }

    /**
     * @param string $paymentTemplateId Payment template Id
     * @param string $sessionId         Session Id
     * @return PaymentTemplate
     */
    public function retrievePaymentTemplate(
        string $paymentTemplateId,
        string $sessionId
    ): PaymentTemplate {
        $command = $this->commandFactory->getCommand(
            RetrievePaymentTemplateCommand::class,
            $this->adapter,
            $paymentTemplateId,
            $sessionId
        );

        return $command->execute();
    }
}
