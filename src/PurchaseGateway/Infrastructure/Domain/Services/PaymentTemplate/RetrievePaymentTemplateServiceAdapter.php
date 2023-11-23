<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Services\RetrievePaymentTemplateAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\RetrievePaymentTemplateException;

class RetrievePaymentTemplateServiceAdapter extends BasePaymentTemplateAdapter implements RetrievePaymentTemplateAdapter
{
    /**
     * @param string $paymentTemplateId Payment Template Id
     * @param string $sessionId         Session Id
     * @return PaymentTemplate
     * @throws Exceptions\PaymentTemplateCodeTypeException
     * @throws Exceptions\PaymentTemplateDataNotFoundException
     * @throws RetrievePaymentTemplateException
     * @throws Exception
     */
    public function retrievePaymentTemplate(
        string $paymentTemplateId,
        string $sessionId
    ): PaymentTemplate {
        $result = $this->client->retrievePaymentTemplate($paymentTemplateId, $sessionId);

        return $this->translator->translateRetrievePaymentTemplate($paymentTemplateId, $result);
    }
}
