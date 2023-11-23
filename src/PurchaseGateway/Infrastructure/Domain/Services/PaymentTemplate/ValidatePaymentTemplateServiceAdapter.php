<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate;

use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Services\ValidatePaymentTemplateAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\RetrievePaymentTemplateException;

class ValidatePaymentTemplateServiceAdapter extends BasePaymentTemplateAdapter implements ValidatePaymentTemplateAdapter
{
    /**
     * @param string $paymentTemplateId Payment Template Id
     * @param string $lastFour          Last four
     * @param string $sessionId         Session Id
     * @return PaymentTemplate
     * @throws Exceptions\PaymentTemplateCodeTypeException
     * @throws Exceptions\PaymentTemplateDataNotFoundException
     * @throws RetrievePaymentTemplateException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidPaymentTemplateLastFour
     */
    public function validatePaymentTemplate(
        string $paymentTemplateId,
        string $lastFour,
        string $sessionId
    ): PaymentTemplate {
        $result = $this->client->validatePaymentTemplate($paymentTemplateId, $lastFour, $sessionId);

        return $this->translator->translateValidatePaymentTemplate($paymentTemplateId, $result);
    }
}
