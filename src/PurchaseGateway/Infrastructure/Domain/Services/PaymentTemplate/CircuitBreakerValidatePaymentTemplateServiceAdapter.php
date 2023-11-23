<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate;

use Odesk\Phystrix\CommandFactory;
use Odesk\Phystrix\Exception\RuntimeException;
use ProBillerNG\CircuitBreaker\BadRequestException;
use ProBillerNG\CircuitBreaker\CircuitBreaker;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Services\ValidatePaymentTemplateAdapter;

class CircuitBreakerValidatePaymentTemplateServiceAdapter extends CircuitBreaker implements ValidatePaymentTemplateAdapter
{
    /**
     * @var ValidatePaymentTemplateAdapter
     */
    private $adapter;

    /**
     * CircuitBreakerValidatePaymentTemplateServiceAdapter constructor.
     * @param CommandFactory                        $commandFactory Command
     * @param ValidatePaymentTemplateServiceAdapter $adapter        Adapter
     */
    public function __construct(
        CommandFactory $commandFactory,
        ValidatePaymentTemplateServiceAdapter $adapter
    ) {
        parent::__construct($commandFactory);
        $this->adapter = $adapter;
    }

    /**
     * @param string $paymentTemplateId Payment template Id
     * @param string $lastFour          Last four
     * @param string $sessionId         Session Id
     *
     * @return PaymentTemplate
     *
     * @throws \Exception
     */
    public function validatePaymentTemplate(
        string $paymentTemplateId,
        string $lastFour,
        string $sessionId
    ): PaymentTemplate {
        $command = $this->commandFactory->getCommand(
            ValidatePaymentTemplateCommand::class,
            $this->adapter,
            $paymentTemplateId,
            $lastFour,
            $sessionId
        );

        try {
            return $command->execute();
        } catch (BadRequestException $exception) {
            // This is the exception that was encapsulated in the bad request exception
            // to allow circuit breaker logic bypass
            throw $exception->getPrevious();
        } catch (RuntimeException $exception) {
            // This is the exception thrown by us based on the service call
            // Extracting it from the CB runtime exception and throwing it further
            throw $exception->getFallbackException();
        }
    }
}
