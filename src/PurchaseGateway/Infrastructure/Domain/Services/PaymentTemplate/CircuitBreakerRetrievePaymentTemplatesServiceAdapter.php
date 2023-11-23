<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate;

use Odesk\Phystrix\CommandFactory;
use ProBillerNG\CircuitBreaker\CircuitBreaker;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Domain\Services\RetrievePaymentTemplatesAdapter;

class CircuitBreakerRetrievePaymentTemplatesServiceAdapter extends CircuitBreaker implements RetrievePaymentTemplatesAdapter
{
    /**
     * @var RetrievePaymentTemplatesAdapter
     */
    private $adapter;

    /**
     * CircuitBreakerRetrievePaymentTemplatesServiceAdapter constructor.
     * @param CommandFactory                         $commandFactory Command
     * @param RetrievePaymentTemplatesServiceAdapter $adapter        Adapter
     */
    public function __construct(
        CommandFactory $commandFactory,
        RetrievePaymentTemplatesServiceAdapter $adapter
    ) {
        parent::__construct($commandFactory);
        $this->adapter = $adapter;
    }

    /**
     * @param string $memberId    Member Id
     * @param string $paymentType Payment type
     * @param string $sessionId   Session Id
     * @return PaymentTemplateCollection
     */
    public function retrieveAllPaymentTemplates(
        string $memberId,
        string $paymentType,
        string $sessionId
    ): PaymentTemplateCollection {
        $command = $this->commandFactory->getCommand(
            RetrievePaymentTemplatesCommand::class,
            $this->adapter,
            $memberId,
            $paymentType,
            $sessionId
        );

        return $command->execute();
    }
}
