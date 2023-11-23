<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs;

use ProBillerNG\CircuitBreaker\ExternalCommand;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;

/**
 * @deprecated
 * Class RetrieveFraudAdviceCsCommand
 * @package ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs
 */
class RetrieveFraudAdviceCsCommand extends ExternalCommand
{
    /** @var FraudAdviceCsAdapter */
    private $adapter;

    /** @var PaymentTemplateCollection */
    private $paymentTemplateCollection;

    /** @var SessionId */
    private $sessionId;

    /**
     * RetrieveFraudAdviceCommand constructor.
     * @param FraudAdviceCsAdapter      $adapter                   Fraud Advice Adapter
     * @param PaymentTemplateCollection $paymentTemplateCollection PaymentTemplateCollection
     * @param string                    $sessionId                 Session Id
     */
    public function __construct(
        FraudAdviceCsAdapter $adapter,
        PaymentTemplateCollection $paymentTemplateCollection,
        string $sessionId
    ) {
        $this->adapter = $adapter;

        $this->paymentTemplateCollection = $paymentTemplateCollection;
        $this->sessionId                 = $sessionId;
    }

    /**
     * @return void
     * @throws Exceptions\FraudAdviceCsCodeApiException
     * @throws Exceptions\FraudAdviceCsCodeTypeException
     * @throws \ProBillerNG\Logger\Exception
     */
    protected function run(): void
    {
        $this->adapter->retrieveAdvice(
            $this->paymentTemplateCollection,
            $this->sessionId
        );
    }

    /**
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    protected function getFallback(): void
    {
        Log::info('FraudAdviceCs service error.');
        $exception = $this->getExecutionException();
        if ($exception instanceof \Throwable) {
            Log::logException($exception);
        }
    }
}
