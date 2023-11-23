<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\Biller;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Domain\Services\RetrievePaymentTemplatesAdapter;

class RetrievePaymentTemplatesServiceAdapter extends BasePaymentTemplateAdapter implements RetrievePaymentTemplatesAdapter
{
    /**
     * @param string $memberId    Member Id
     * @param string $paymentType Payment type
     * @param string $sessionId   Session Id
     * @return PaymentTemplateCollection
     * @throws Exceptions\PaymentTemplateCodeApiException
     * @throws Exceptions\PaymentTemplateCodeErrorException
     * @throws Exceptions\PaymentTemplateCodeTypeException
     * @throws Exception
     */
    public function retrieveAllPaymentTemplates(
        string $memberId,
        string $paymentType,
        string $sessionId
    ): PaymentTemplateCollection {
        $paymentTemplates = new PaymentTemplateCollection();

        $result = $this->client->retrieveAllPaymentTemplatesForMember(
            $memberId,
            $paymentType,
            $sessionId
        );

        $billerPaymentTemplates = $this->translator->translateRetrieveAllPaymentTemplatesForMember($result);

        foreach ($billerPaymentTemplates as $billerPaymentTemplate) {
            $paymentTemplates->offsetSet(
                $billerPaymentTemplate->templateId(),
                $billerPaymentTemplate
            );
        }

        return $paymentTemplates->sortByLastUsedDateDesc();
    }
}
