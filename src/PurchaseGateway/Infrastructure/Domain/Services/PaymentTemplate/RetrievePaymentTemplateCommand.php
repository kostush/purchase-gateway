<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate;

use ProBillerNG\CircuitBreaker\ExternalCommand;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\RetrievePaymentTemplateException;

class RetrievePaymentTemplateCommand extends ExternalCommand
{
    /**
     * @var RetrievePaymentTemplateServiceAdapter
     */
    private $adapter;

    /**
     * @var string
     */
    private $paymentTemplateId;

    /**
     * @var string
     */
    private $sessionId;

    /**
     * RetrievePaymentTemplateCommand constructor.
     * @param RetrievePaymentTemplateServiceAdapter $adapter           Adapter
     * @param string                                $paymentTemplateId Payment template Id
     * @param string                                $sessionId         Session Id
     */
    public function __construct(
        RetrievePaymentTemplateServiceAdapter $adapter,
        string $paymentTemplateId,
        string $sessionId
    ) {
        $this->adapter           = $adapter;
        $this->paymentTemplateId = $paymentTemplateId;
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
        return $this->adapter->retrievePaymentTemplate(
            $this->paymentTemplateId,
            $this->sessionId
        );
    }

    /**
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws RetrievePaymentTemplateException
     */
    protected function getFallback(): void
    {
        $exception = $this->getExecutionException();
        if ($exception instanceof \Throwable) {
            Log::logException($exception);
        }

        throw new RetrievePaymentTemplateException();
    }
}
