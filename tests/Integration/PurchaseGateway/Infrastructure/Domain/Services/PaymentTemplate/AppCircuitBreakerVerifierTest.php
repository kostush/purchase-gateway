<?php
declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate;

use Odesk\Phystrix\ApcStateStorage;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\ApcCircuitBreakerVerifier;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\RetrievePaymentTemplatesCommand;
use Tests\IntegrationTestCase;

class AppCircuitBreakerVerifierTest extends IntegrationTestCase
{
    /**
     * @test
     * @return void
     */
    public function it_should_return_false_when_the_circuit_is_closed(): void
    {
        $circuitBreakerVerifier = new ApcCircuitBreakerVerifier();
        $result                 = $circuitBreakerVerifier->isOpen();

        $this->assertFalse($result);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_true_when_the_circuit_is_open(): void
    {
        $initialStatus = (boolean) apc_fetch(
            ApcStateStorage::CACHE_PREFIX . RetrievePaymentTemplatesCommand::class . ApcStateStorage::OPENED_NAME
        );

        apc_store(
            ApcStateStorage::CACHE_PREFIX . RetrievePaymentTemplatesCommand::class . ApcStateStorage::OPENED_NAME,
            true
        );

        $circuitBreakerVerifier = new ApcCircuitBreakerVerifier();
        $result                 = $circuitBreakerVerifier->isOpen();

        apc_store(
            ApcStateStorage::CACHE_PREFIX . RetrievePaymentTemplatesCommand::class . ApcStateStorage::OPENED_NAME,
            $initialStatus
        );

        $this->assertTrue($result);
    }
}
