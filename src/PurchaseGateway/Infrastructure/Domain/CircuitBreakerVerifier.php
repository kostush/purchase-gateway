<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain;

use ProBillerNG\PurchaseGateway\Domain\Services\ServiceStatusVerifier;
use ProBillerNG\PurchaseGateway\Infrastructure\Services\Cascade\ApcCircuitBreakerVerifier as CascadeCircuitBreaker;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice\ApcCircuitBreakerVerifier as FraudCircuitBreaker;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BillerMapping\ApcCircuitBreakerVerifier as BillerMappingCircuitBreaker;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\ApcCircuitBreakerVerifier as EmailCircuitBreaker;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\ApcCircuitBreakerVerifier as TransactionCircuitBreaker;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\ApcCircuitBreakerVerifier as PaymentTemplateCircuitBreaker;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs\ApcCircuitBreakerVerifier as FraudCsCircuitBreaker;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\MemberProfileGateway\ApcCircuitBreakerVerifier as MemberProfileCircuitBreaker;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation\ApcCircuitBreakerVerifier as RetrieveFraudRecommendation;

class CircuitBreakerVerifier implements ServiceStatusVerifier
{
    /** @var FraudCircuitBreaker */
    private $fraudAdviceCircuitBreaker;

    /** @var CascadeCircuitBreaker */
    private $cascadeCircuitBreaker;

    /** @var BillerMappingCircuitBreaker */
    private $billerMappingCircuitBreaker;

    /** @var EmailCircuitBreaker */
    private $emailCircuitBreaker;

    /** @var TransactionCircuitBreaker */
    private $transactionCircuitBreaker;

    /** @var PaymentTemplateCircuitBreaker */
    private $paymentTemplateCircuitBreaker;

    /** @var FraudCsCircuitBreaker */
    private $fraudAdviceCsCircuitBreaker;

    /** @var MemberProfileCircuitBreaker */
    private $memberProfileCircuitBreaker;

    /** @var RetrieveFraudRecommendation */
    private $retrieveFraudRecommendation;

    /**
     * CircuitBreakerVerifier constructor.
     * @param FraudCircuitBreaker           $fraudAdviceCircuitBreaker     Fraud Circuit Breaker
     * @param CascadeCircuitBreaker         $cascadeCircuitBreaker         Cascade Circuit Breaker
     * @param BillerMappingCircuitBreaker   $billerMappingCircuitBreaker   Biller Mapping Circuit Breaker
     * @param EmailCircuitBreaker           $emailCircuitBreaker           Email Circuit Breaker
     * @param TransactionCircuitBreaker     $transactionCircuitBreaker     Transaction Circuit Breaker
     * @param PaymentTemplateCircuitBreaker $paymentTemplateCircuitBreaker Payment Template Circuit Breaker
     * @param FraudCsCircuitBreaker         $fraudAdviceCsCircuitBreaker   FraudCs Circuit Breaker
     * @param MemberProfileCircuitBreaker   $memberProfileCircuitBreaker   MemberProfile Circuit Breaker
     * @param RetrieveFraudRecommendation   $retrieveFraudRecommendation
     */
    public function __construct(
        FraudCircuitBreaker $fraudAdviceCircuitBreaker,
        CascadeCircuitBreaker $cascadeCircuitBreaker,
        BillerMappingCircuitBreaker $billerMappingCircuitBreaker,
        EmailCircuitBreaker $emailCircuitBreaker,
        TransactionCircuitBreaker $transactionCircuitBreaker,
        PaymentTemplateCircuitBreaker $paymentTemplateCircuitBreaker,
        FraudCsCircuitBreaker $fraudAdviceCsCircuitBreaker,
        MemberProfileCircuitBreaker $memberProfileCircuitBreaker,
        RetrieveFraudRecommendation $retrieveFraudRecommendation
    ) {
        $this->fraudAdviceCircuitBreaker     = $fraudAdviceCircuitBreaker;
        $this->cascadeCircuitBreaker         = $cascadeCircuitBreaker;
        $this->billerMappingCircuitBreaker   = $billerMappingCircuitBreaker;
        $this->emailCircuitBreaker           = $emailCircuitBreaker;
        $this->transactionCircuitBreaker     = $transactionCircuitBreaker;
        $this->paymentTemplateCircuitBreaker = $paymentTemplateCircuitBreaker;
        $this->fraudAdviceCsCircuitBreaker   = $fraudAdviceCsCircuitBreaker;
        $this->memberProfileCircuitBreaker   = $memberProfileCircuitBreaker;
        $this->retrieveFraudRecommendation   = $retrieveFraudRecommendation;
    }

    /**
     * @return bool
     */
    public function fraudServiceStatus(): bool
    {
        return $this->fraudAdviceCircuitBreaker->isOpen();
    }

    /**
     * @return bool
     */
    public function cascadeServiceStatus(): bool
    {
        return $this->cascadeCircuitBreaker->isOpen();
    }

    /**
     * @return bool
     */
    public function billerMappingServiceStatus(): bool
    {
        return $this->billerMappingCircuitBreaker->isOpen();
    }

    /**
     * @return bool
     */
    public function emailServiceStatus(): bool
    {
        return $this->emailCircuitBreaker->isOpen();
    }

    /**
     * @return bool
     */
    public function transactionServiceStatus(): bool
    {
        return $this->transactionCircuitBreaker->isOpen();
    }

    /**
     * @return bool
     */
    public function paymentTemplateServiceStatus(): bool
    {
        return $this->paymentTemplateCircuitBreaker->isOpen();
    }

    /**
     * @deprecated
     * @return bool
     */
    public function fraudCsServiceStatus(): bool
    {
        return $this->fraudAdviceCsCircuitBreaker->isOpen();
    }

    /**
     * @return bool
     */
    public function memberProfileGatewayStatus(): bool
    {
        return $this->memberProfileCircuitBreaker->isOpen();
    }

    /**
     * @return bool
     */
    public function retrieveFraudRecommendation(): bool
    {
        return $this->retrieveFraudRecommendation->isOpen();
    }
}
