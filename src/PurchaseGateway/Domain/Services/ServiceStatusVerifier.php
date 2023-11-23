<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

interface ServiceStatusVerifier
{
    /**
     * @return bool
     */
    public function fraudServiceStatus() : bool;

    /**
     * @return bool
     */
    public function cascadeServiceStatus() : bool;

    /**
     * @return bool
     */
    public function billerMappingServiceStatus() : bool;

    /**
     * @return bool
     */
    public function emailServiceStatus() : bool;

    /**
     * @return bool
     */
    public function transactionServiceStatus() : bool;

    /**
     * @return bool
     */
    public function paymentTemplateServiceStatus() : bool;

    /**
     * @return bool
     */
    public function fraudCsServiceStatus() : bool;

    /**
     * @return bool
     */
    public function memberProfileGatewayStatus() : bool;

    /**
     * @return bool
     */
    public function retrieveFraudRecommendation(): bool;
}
