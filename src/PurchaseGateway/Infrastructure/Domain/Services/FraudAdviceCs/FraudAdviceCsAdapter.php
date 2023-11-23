<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs;

use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudCsAdapter;

/**
 * @deprecated
 * Class FraudAdviceCsAdapter
 * @package ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs
 */
class FraudAdviceCsAdapter implements FraudCsAdapter
{
    /**
     * @var FraudAdviceCsClient
     */
    private $fraudServiceClient;

    /**
     * @var FraudAdviceCsTranslator
     */
    private $fraudServiceTranslator;

    /**
     * CascadeAdapter constructor.
     * @param FraudAdviceCsClient     $fraudServiceClient     Fraud Service Client
     * @param FraudAdviceCsTranslator $fraudServiceTranslator Fraud Service Translator
     */
    public function __construct(
        FraudAdviceCsClient $fraudServiceClient,
        FraudAdviceCsTranslator $fraudServiceTranslator
    ) {
        $this->fraudServiceClient     = $fraudServiceClient;
        $this->fraudServiceTranslator = $fraudServiceTranslator;
    }

    /**
     * @param PaymentTemplateCollection $paymentTemplateCollection Payment Template Collection
     * @param string                    $sessionId                 Session Id
     * @return void
     * @throws Exceptions\FraudAdviceCsCodeApiException
     * @throws \ProBillerNG\Logger\Exception
     * @throws Exceptions\FraudAdviceCsCodeTypeException
     */
    public function retrieveAdvice(PaymentTemplateCollection $paymentTemplateCollection, string $sessionId): void
    {
        $fraudServiceResult = $this->fraudServiceClient->retrieve($paymentTemplateCollection, $sessionId);
        $this->fraudServiceTranslator->translate($paymentTemplateCollection, $fraudServiceResult);
    }
}
