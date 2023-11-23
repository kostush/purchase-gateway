<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services;

use ProBillerNG\PurchaseGateway\Infrastructure\Domain\CircuitBreakerVerifier;
use Tests\UnitTestCase;

class CircuitBreakerVerifierTest extends UnitTestCase
{
    /**
     * @test
     * @return CircuitBreakerVerifier
     */
    public function circuit_breaker_verifier_should_return_a_boolean_value_when_fraud_advice_method_is_called(): CircuitBreakerVerifier
    {
        $circuitBreakerVerifier = app(CircuitBreakerVerifier::class);

        $this->assertIsBool($circuitBreakerVerifier->fraudServiceStatus());

        return $circuitBreakerVerifier;
    }

    /**
     * @test
     * @depends circuit_breaker_verifier_should_return_a_boolean_value_when_fraud_advice_method_is_called
     * @param CircuitBreakerVerifier $circuitBreakerVerifier CircuitBreakerVerifier
     * @return void
     */
    public function circuit_breaker_verifier_should_return_a_boolean_value_when_cascade_method_is_called(
        CircuitBreakerVerifier $circuitBreakerVerifier
    ): void {
        $this->assertIsBool($circuitBreakerVerifier->cascadeServiceStatus());
    }

    /**
     * @test
     * @depends circuit_breaker_verifier_should_return_a_boolean_value_when_fraud_advice_method_is_called
     * @param CircuitBreakerVerifier $circuitBreakerVerifier CircuitBreakerVerifier
     * @return void
     */
    public function circuit_breaker_verifier_should_return_a_boolean_value_when_biller_mapping_method_is_called(
        CircuitBreakerVerifier $circuitBreakerVerifier
    ): void {
        $this->assertIsBool($circuitBreakerVerifier->billerMappingServiceStatus());
    }

    /**
     * @test
     * @depends circuit_breaker_verifier_should_return_a_boolean_value_when_fraud_advice_method_is_called
     * @param CircuitBreakerVerifier $circuitBreakerVerifier CircuitBreakerVerifier
     * @return void
     */
    public function circuit_breaker_verifier_should_return_a_boolean_value_when_email_service_method_is_called(
        CircuitBreakerVerifier $circuitBreakerVerifier
    ): void {
        $this->assertIsBool($circuitBreakerVerifier->emailServiceStatus());
    }

    /**
     * @test
     * @depends circuit_breaker_verifier_should_return_a_boolean_value_when_fraud_advice_method_is_called
     * @param CircuitBreakerVerifier $circuitBreakerVerifier CircuitBreakerVerifier
     * @return void
     */
    public function circuit_breaker_verifier_should_return_a_boolean_value_when_transaction_service_method_is_called(
        CircuitBreakerVerifier $circuitBreakerVerifier
    ): void {
        $this->assertIsBool($circuitBreakerVerifier->transactionServiceStatus());
    }

    /**
     * @test
     * @depends circuit_breaker_verifier_should_return_a_boolean_value_when_fraud_advice_method_is_called
     * @param CircuitBreakerVerifier $circuitBreakerVerifier CircuitBreakerVerifier
     * @return void
     */
    public function circuit_breaker_verifier_should_return_a_boolean_value_when_payment_template_service_method_is_called(
        CircuitBreakerVerifier $circuitBreakerVerifier
    ): void {
        $this->assertIsBool($circuitBreakerVerifier->paymentTemplateServiceStatus());
    }

    /**
     * @deprecated
     * @test
     * @depends circuit_breaker_verifier_should_return_a_boolean_value_when_fraud_advice_method_is_called
     * @param CircuitBreakerVerifier $circuitBreakerVerifier CircuitBreakerVerifier
     * @return void
     */
    public function circuit_breaker_verifier_should_return_a_boolean_value_when_fraud_cs_service_status_method_is_called(
        CircuitBreakerVerifier $circuitBreakerVerifier
    ): void {
        $this->assertIsBool($circuitBreakerVerifier->fraudCsServiceStatus());
    }

    /**
     * @test
     * @depends circuit_breaker_verifier_should_return_a_boolean_value_when_fraud_advice_method_is_called
     * @param CircuitBreakerVerifier $circuitBreakerVerifier CircuitBreakerVerifier
     * @return void
     */
    public function circuit_breaker_verifier_should_return_a_boolean_value_when_member_profile_service_method_is_called(
        CircuitBreakerVerifier $circuitBreakerVerifier
    ): void {
        $this->assertIsBool($circuitBreakerVerifier->memberProfileGatewayStatus());
    }
}
