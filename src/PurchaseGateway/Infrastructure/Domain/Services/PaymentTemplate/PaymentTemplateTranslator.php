<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate;

use Illuminate\Http\Response;
use ProBillerNG\Logger\Exception;
use ProbillerNG\PaymentTemplateServiceClient\Model\InlineResponse404;
use ProbillerNG\PaymentTemplateServiceClient\Model\InlineResponse500;
use ProbillerNG\PaymentTemplateServiceClient\Model\PaymentTemplate as ClientPaymentTemplate;
use ProbillerNG\PaymentTemplateServiceClient\Model\RetrieveResponse;
use ProbillerNG\PaymentTemplateServiceClient\Model\ValidatePaymentTemplateResponse;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\PaymentTemplateCodeTypeException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\PaymentTemplateDataNotFoundException;

class PaymentTemplateTranslator
{
    /**
     * @param mixed $result Result
     * @return PaymentTemplateCollection
     * @throws Exceptions\PaymentTemplateCodeErrorException
     * @throws PaymentTemplateCodeTypeException
     * @throws Exception
     */
    public function translateRetrieveAllPaymentTemplatesForMember($result): PaymentTemplateCollection
    {
        if ($result instanceof InlineResponse500) {
            throw new Exceptions\PaymentTemplateCodeErrorException(null, $result->getError(), $result->getCode());
        }

        $paymentTemplateCollection = new PaymentTemplateCollection();

        if (is_array($result)) {
            foreach ($result as $paymentTemplate) {
                if (!($paymentTemplate instanceof ClientPaymentTemplate)) {
                    throw new PaymentTemplateCodeTypeException(
                        null,
                        ClientPaymentTemplate::class,
                        Response::HTTP_INTERNAL_SERVER_ERROR
                    );
                }

                $paymentTemplateCollection->offsetSet(
                    $paymentTemplate->getTemplateId(),
                    PaymentTemplate::create(
                        $paymentTemplate->getTemplateId(),
                        $paymentTemplate->getFirstSix(),
                        "", // This is not retrieved in the initial request
                        (string) $paymentTemplate->getExpirationYear(),
                        (string) $paymentTemplate->getExpirationMonth(),
                        $paymentTemplate->getLastUsedDate(),
                        $paymentTemplate->getCreatedAt(),
                        (string) $paymentTemplate->getBillerName(),
                        [] // This is not retrieved in the initial request
                    )
                );
            }
        }

        return $paymentTemplateCollection;
    }

    /**
     * @param string $paymentTemplateId Payment template id.
     * @param mixed  $result            Result.
     * @return PaymentTemplate
     * @throws PaymentTemplateCodeTypeException
     * @throws Exception
     */
    public function translateRetrievePaymentTemplate(
        string $paymentTemplateId,
        $result
    ): PaymentTemplate {
        if (!($result instanceof RetrieveResponse)) {
            throw new PaymentTemplateCodeTypeException(
                null,
                RetrieveResponse::class,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $this->createPaymentTemplate($paymentTemplateId, $result);
    }

    /**
     * @param string $paymentTemplateId Payment template Id
     * @param mixed  $result            Result
     * @return PaymentTemplate
     * @throws PaymentTemplateCodeTypeException
     * @throws PaymentTemplateDataNotFoundException
     * @throws Exception
     */
    public function translateValidatePaymentTemplate(string $paymentTemplateId, $result): PaymentTemplate
    {
        if ($result instanceof InlineResponse404) {
            throw new PaymentTemplateDataNotFoundException((string) $paymentTemplateId);
        }

        if (!($result instanceof ValidatePaymentTemplateResponse)) {
            throw new PaymentTemplateCodeTypeException(
                null,
                ValidatePaymentTemplateResponse::class,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $this->createPaymentTemplate($paymentTemplateId, $result);
    }

    /**
     * @param string                                           $paymentTemplateId Payment template Id
     * @param ValidatePaymentTemplateResponse|RetrieveResponse $result            Result From Payment Template
     * @return PaymentTemplate
     */
    protected function createPaymentTemplate(string $paymentTemplateId, $result): PaymentTemplate
    {
        /** @var string $billerName */
        $billerName = $result->getPaymentTemplate()->getBillerName();
        /** @var array $billerFields */
        $resultBillerFields = $result->getPaymentTemplate()->getBillerFields();
        /** @var string|null $lastFour */
        $lastFour = $result->getPaymentTemplate()->getLastFour();

        if ($billerName == NetbillingBiller::BILLER_NAME) {
            if (!empty($resultBillerFields['originId'])) {
                $hash = base64_encode("CS:" . $resultBillerFields['originId'] . ":" . $lastFour);
            } else {
                $hash = $resultBillerFields['cardHash']; // This is already base64 encoded.
            }

            $billerFields = [
                'cardHash'   => $hash,
                'binRouting' => $resultBillerFields['binRouting'] ?? ''
            ];
        }

        if ($billerName == RocketgateBiller::BILLER_NAME || $billerName == EpochBiller::BILLER_NAME) {
            $billerFields = $resultBillerFields;
        }

        return PaymentTemplate::create(
            $paymentTemplateId,
            $result->getPaymentTemplate()->getFirstSix() ?? '',
            $lastFour ?? '',
            (string) $result->getPaymentTemplate()->getExpirationYear(),
            (string) $result->getPaymentTemplate()->getExpirationMonth(),
            $result->getPaymentTemplate()->getLastUsedDate(),
            $result->getPaymentTemplate()->getCreatedAt(),
            $result->getPaymentTemplate()->getBillerName(),
            $billerFields ?? []
        );
    }
}
