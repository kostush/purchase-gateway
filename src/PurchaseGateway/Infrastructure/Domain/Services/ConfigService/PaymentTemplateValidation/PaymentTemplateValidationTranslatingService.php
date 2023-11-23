<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\PaymentTemplateValidation;

use Probiller\Service\Config\GetPaymentTemplateValidationRequest;
use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudCsService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use const Grpc\STATUS_OK;

class PaymentTemplateValidationTranslatingService implements FraudCsService
{
    /**
     * @var ConfigService
     */
    private $configService;

    /**
     * BillerMappingTranslatingService constructor.
     *
     * @param ConfigService $configService
     */
    public function __construct(
        ConfigService $configService
    ) {
        $this->configService = $configService;
    }

    /**
     * @param PaymentTemplateCollection $paymentTemplateCollection Payment Template Collection
     * @param string                    $siteId
     * @param int                       $initialDays
     *
     * @return void
     * @throws Exception
     */
    public function retrieveAdviceFromConfig(
        PaymentTemplateCollection $paymentTemplateCollection,
        string $siteId,
        int $initialDays
    ): void {
        try {
            Log::info(
                'RetrievePaymentTemplateValidation REQUEST starting retrieve payment template validation configuration from config service',
                [
                    'requestType'    => 'GRPC',
                    'method'         => 'GetPaymentTemplateValidationConfig',
                    'host'           => env('CONFIG_SERVICE_HOST'),
                    'requestPayload' => [
                        'siteId' => $siteId
                    ]
                ]
            );

            $templateOwnerFilterRequest = new GetPaymentTemplateValidationRequest\PaymentTemplateValidationOwnerFilter();
            $templateOwnerFilterRequest->setSiteId($siteId);

            $validationRequest = new GetPaymentTemplateValidationRequest();
            $validationRequest->setOwner($templateOwnerFilterRequest);

            [
                $configReply,
                $responseStatus
            ] = $this->configService->getClient()->GetPaymentTemplateValidationConfig(
                $validationRequest,
                $this->configService->getMetadata()
            )->wait();

            if ($responseStatus->code !== STATUS_OK) {
                Log::warning(
                    'RetrievePaymentTemplateValidation RESPONSE fail to retrieve payment template validation configuration from config service',
                    [
                        'responseType' => 'GRPC',
                        'method'       => 'GetPaymentTemplateValidationConfig',
                        'host'         => env('CONFIG_SERVICE_HOST'),
                        'response'     => [
                            'PaymentTemplateValidationConfig' => $configReply
                        ]
                    ]
                );

                return;
            }

            $paymentTemplateInfo = [
                "paymentTemplateValidationId"         => $configReply->getPaymentTemplateValidationId(),
                "subscriptionPurchaseEnabled"         => $configReply->getSubscriptionPurchaseEnabled(),
                "subscriptionTrialUpgradeEnabled"     => $configReply->getSubscriptionTrialUpgradeEnabled(),
                "subscriptionUpgradeEnabled"          => $configReply->getSubscriptionUpgradeEnabled(),
                "subscriptionExpiredRenewEnabled"     => $configReply->getSubscriptionExpiredRenewEnabled(),
                "subscriptionRecurringChargeEnabled"  => $configReply->getSubscriptionRecurringChargeEnabled(),
                "singleChargePurchaseEnabled"         => $configReply->getSingleChargePurchaseEnabled(),
                "recurringItemPurchaseEnabled"        => $configReply->getRecurringItemPurchaseEnabled(),
                "recurringItemRecurringChargeEnabled" => $configReply->getRecurringItemRecurringChargeEnabled(),
                "siteId"                              => $configReply->getSiteId(),
                "owner"                               => $configReply->getOwner()
            ];

            Log::info(
                'RetrievePaymentTemplateValidation RESPONSE payment template validation configuration from config service',
                [
                    'responseType' => 'GRPC',
                    'method'       => 'GetPaymentTemplateValidationConfig',
                    'host'         => env('CONFIG_SERVICE_HOST'),
                    'response'     => [
                        'PaymentTemplateValidationConfig' => $paymentTemplateInfo
                    ]
                ]
            );

            PaymentTemplateValidationTranslator::translate(
                $paymentTemplateCollection,
                $paymentTemplateInfo,
                $initialDays
            );
        } catch (\Throwable $exception) {
            Log::logException($exception);

            return;
        }
    }

    /**
     * @param PaymentTemplateCollection $paymentTemplateCollection
     * @param string                    $sessionId
     */
    public function retrieveAdvice(
        PaymentTemplateCollection $paymentTemplateCollection,
        string $sessionId
    ): void {
        throw new \BadMethodCallException('This method is not applicable for Config Service for payment template validation config');
    }
}
