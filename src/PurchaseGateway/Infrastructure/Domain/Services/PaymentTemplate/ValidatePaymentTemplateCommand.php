<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate;

use ProBillerNG\CircuitBreaker\BadRequestException;
use ProBillerNG\CircuitBreaker\ExternalCommand;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidPaymentTemplateLastFour;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\RetrievePaymentTemplateException;

class ValidatePaymentTemplateCommand extends ExternalCommand
{
    /**
     * @var ValidatePaymentTemplateServiceAdapter
     */
    private $adapter;

    /**
     * @var string
     */
    private $paymentTemplateId;

    /**
     * @var string
     */
    private $lastFour;

    /**
     * @var string
     */
    private $sessionId;

    /**
     * RetrievePaymentTemplateCommand constructor.
     * @param ValidatePaymentTemplateServiceAdapter $adapter           Adapter
     * @param string                                $paymentTemplateId Payment template Id
     * @param string                                $lastFour          Last four
     * @param string                                $sessionId         Session Id
     */
    public function __construct(
        ValidatePaymentTemplateServiceAdapter $adapter,
        string $paymentTemplateId,
        string $lastFour,
        string $sessionId
    ) {
        $this->adapter           = $adapter;
        $this->paymentTemplateId = $paymentTemplateId;
        $this->lastFour          = $lastFour;
        $this->sessionId         = $sessionId;
    }

    /**
     * @return PaymentTemplate
     * @throws Exceptions\PaymentTemplateCodeTypeException
     * @throws Exceptions\PaymentTemplateDataNotFoundException
     * @throws Exceptions\RetrievePaymentTemplateException
     * @throws \ProBillerNG\Logger\Exception
     */
    protected function run(): PaymentTemplate
    {
        try {
            return $this->adapter->validatePaymentTemplate(
                $this->paymentTemplateId,
                $this->lastFour,
                $this->sessionId
            );
        } catch (InvalidPaymentTemplateLastFour $exception) {
            // For validation exceptions, throw bad request exception to avoid circuit breaker logic
            // This way we can propagate errors and do proper handling
            throw new BadRequestException('', 0, $exception);
        }
    }

    /**
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws RetrievePaymentTemplateException
     * @throws \Throwable
     */
    protected function getFallback(): void
    {
        $exception = $this->getExecutionException();

        throw new RetrievePaymentTemplateException($exception);
    }
}
