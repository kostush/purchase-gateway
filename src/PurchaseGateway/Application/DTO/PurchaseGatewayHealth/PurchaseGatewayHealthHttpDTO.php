<?php

namespace ProBillerNG\PurchaseGateway\Application\DTO\PurchaseGatewayHealth;

use Illuminate\Contracts\Support\Arrayable;

class PurchaseGatewayHealthHttpDTO implements Arrayable
{
    const STATUS                                 = 'status';
    const NUMBER_OF_CONFIGURATIONS               = 'number of site configurations';
    const POSTBACK_QUEUE_LENGTH                  = 'number of postback jobs in queue';
    const POSTBACK_FAILED_JOBS                   = 'number of failed postback jobs since last cleanup';
    const FRAUD_ADVICE_SERVICE_COMMUNICATION     = 'FraudAdviceService communication';
    const CASCADE_SERVICE_COMMUNICATION          = 'CascadeService communication';
    const BILLER_MAPPING_SERVICE_COMMUNICATION   = 'BillerMappingService communication';
    const EMAIL_SERVICE_COMMUNICATION            = 'EmailService communication';
    const TRANSACTION_SERVICE_COMMUNICATION      = 'TransactionService communication';
    const PAYMENT_TEMPLATE_SERVICE_COMMUNICATION = 'PaymentTemplateTranslatingService communication';
    const FRAUD_ADVICE_CS_SERVICE_COMMUNICATION  = 'FraudAdviceCsService communication';
    const MEMBER_PROFILE_GATEWAY_COMMUNICATION   = 'MemberProfileGateway communication';
    const BUNDLE_PROJECTION_STATUS               = 'Bundle projection';
    const SITE_PROJECTION_STATUS                 = 'Site projection';
    const FRAUD_ADVICE_RETRIEVE                  = 'Retrieve FraudAdviceService communication';

    /**
     * @var array
     */
    private $response;

    /**
     * PurchaseGatewayQueryHttpDTO constructor.
     * @param array $purchaseGatewayHealth purchase gateway health array.
     * @throws \InvalidArgumentException
     */
    private function __construct(array $purchaseGatewayHealth)
    {
        if (!isset($purchaseGatewayHealth[self::STATUS])
            || !isset($purchaseGatewayHealth[self::NUMBER_OF_CONFIGURATIONS])
        ) {
            throw new \InvalidArgumentException('Wrong array was provided');
        }
        $this->response[self::STATUS]                                 = $purchaseGatewayHealth[self::STATUS];
        $this->response[self::NUMBER_OF_CONFIGURATIONS]               = $purchaseGatewayHealth[self::NUMBER_OF_CONFIGURATIONS];
        $this->response[self::FRAUD_ADVICE_SERVICE_COMMUNICATION]     = $purchaseGatewayHealth[self::FRAUD_ADVICE_SERVICE_COMMUNICATION];
        $this->response[self::FRAUD_ADVICE_CS_SERVICE_COMMUNICATION]  = $purchaseGatewayHealth[self::FRAUD_ADVICE_CS_SERVICE_COMMUNICATION];
        $this->response[self::CASCADE_SERVICE_COMMUNICATION]          = $purchaseGatewayHealth[self::CASCADE_SERVICE_COMMUNICATION];
        $this->response[self::BILLER_MAPPING_SERVICE_COMMUNICATION]   = $purchaseGatewayHealth[self::BILLER_MAPPING_SERVICE_COMMUNICATION];
        $this->response[self::EMAIL_SERVICE_COMMUNICATION]            = $purchaseGatewayHealth[self::EMAIL_SERVICE_COMMUNICATION];
        $this->response[self::TRANSACTION_SERVICE_COMMUNICATION]      = $purchaseGatewayHealth[self::TRANSACTION_SERVICE_COMMUNICATION];
        $this->response[self::PAYMENT_TEMPLATE_SERVICE_COMMUNICATION] = $purchaseGatewayHealth[self::PAYMENT_TEMPLATE_SERVICE_COMMUNICATION];
        $this->response[self::MEMBER_PROFILE_GATEWAY_COMMUNICATION]   = $purchaseGatewayHealth[self::MEMBER_PROFILE_GATEWAY_COMMUNICATION];
        $this->response[self::BUNDLE_PROJECTION_STATUS]               = $purchaseGatewayHealth[self::BUNDLE_PROJECTION_STATUS];
        $this->response[self::SITE_PROJECTION_STATUS]                 = $purchaseGatewayHealth[self::SITE_PROJECTION_STATUS];
        $this->response[self::FRAUD_ADVICE_RETRIEVE]                  = $purchaseGatewayHealth[self::FRAUD_ADVICE_RETRIEVE];

        if (isset($purchaseGatewayHealth[self::POSTBACK_FAILED_JOBS])
            && isset($purchaseGatewayHealth[self::POSTBACK_QUEUE_LENGTH])
        ) {
            $this->response[self::POSTBACK_FAILED_JOBS]  = $purchaseGatewayHealth[self::POSTBACK_FAILED_JOBS];
            $this->response[self::POSTBACK_QUEUE_LENGTH] = $purchaseGatewayHealth[self::POSTBACK_QUEUE_LENGTH];
        }
    }

    /**
     * Creates purchase gateway health response
     *
     * @param array $purchaseGatewayHealth purchase gateway health array.
     * @return PurchaseGatewayHealthHttpDTO
     */
    public static function create(array $purchaseGatewayHealth)
    {
        return new self($purchaseGatewayHealth);
    }

    /**
     * @return array|string
     */
    public function __toString()
    {
        return json_encode($this->response);
    }


    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->response;
    }
}
