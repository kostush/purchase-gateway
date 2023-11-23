<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate;

use ProBillerNG\CircuitBreaker\ExternalCommand;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;

class RetrievePaymentTemplatesCommand extends ExternalCommand
{
    /**
     * @var RetrievePaymentTemplatesServiceAdapter
     */
    private $adapter;

    /**
     * @var string
     */
    private $memberId;

    /**
     * @var string
     */
    private $paymentType;

    /**
     * @var string
     */
    private $sessionId;

    /**
     * RetrievePaymentTemplatesCommand constructor.
     * @param RetrievePaymentTemplatesServiceAdapter $adapter     Adapter
     * @param string                                 $memberId    Member Id
     * @param string                                 $paymentType Payment type
     * @param string                                 $sessionId   Session Id
     */
    public function __construct(
        RetrievePaymentTemplatesServiceAdapter $adapter,
        string $memberId,
        string $paymentType,
        string $sessionId
    ) {
        $this->adapter     = $adapter;
        $this->memberId    = $memberId;
        $this->paymentType = $paymentType;
        $this->sessionId   = $sessionId;
    }

    /**
     * The code to be executed
     *
     * @return mixed
     * @throws Exceptions\PaymentTemplateCodeApiException
     * @throws Exceptions\PaymentTemplateCodeErrorException
     * @throws Exceptions\PaymentTemplateCodeTypeException
     * @throws \ProBillerNG\Logger\Exception
     */
    protected function run(): PaymentTemplateCollection
    {
        return $this->adapter->retrieveAllPaymentTemplates(
            $this->memberId,
            $this->paymentType,
            $this->sessionId
        );
    }

    /**
     * @return PaymentTemplateCollection
     * @throws \ProBillerNG\Logger\Exception
     */
    protected function getFallback(): PaymentTemplateCollection
    {
        Log::error('Cannot retrieve payment templates');
        $exception = $this->getExecutionException();
        if ($exception instanceof \Throwable) {
            Log::logException($exception);
        }

        return new PaymentTemplateCollection();
    }
}
