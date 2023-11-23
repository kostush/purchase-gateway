<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate;

use Illuminate\Http\Response;
use ProbillerNG\PaymentTemplateServiceClient\Api\PaymentTemplateServiceApi;
use ProbillerNG\PaymentTemplateServiceClient\ApiException;
use ProbillerNG\PaymentTemplateServiceClient\Model\InlineResponse404;
use ProbillerNG\PaymentTemplateServiceClient\Model\InlineResponse500;
use ProbillerNG\PaymentTemplateServiceClient\Model\RetrieveResponse;
use ProbillerNG\PaymentTemplateServiceClient\Model\ValidatePaymentTemplateResponse;
use ProBillerNG\PurchaseGateway\Domain\Model\Biller;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidPaymentTemplateLastFour;
use ProBillerNG\PurchaseGateway\Domain\Model\ServiceClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\PaymentTemplateCodeApiException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\PaymentTemplateDataNotFoundException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\RetrievePaymentTemplateException;

class PaymentTemplateClient extends ServiceClient
{
    /**
     * @var PaymentTemplateServiceApi
     */
    private $paymentTemplateApi;

    /**
     * PaymentTemplateClient constructor.
     * @param PaymentTemplateServiceApi $paymentTemplateApi PaymentTemplateApi
     */
    public function __construct(PaymentTemplateServiceApi $paymentTemplateApi)
    {
        $this->paymentTemplateApi = $paymentTemplateApi;
    }

    /**
     * @param string $memberId    Member Id
     * @param string $paymentType Payment type
     * @param string $sessionId   Session Id
     * @return object[]|InlineResponse500
     * @throws PaymentTemplateCodeApiException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function retrieveAllPaymentTemplatesForMember(
        string $memberId,
        string $paymentType,
        string $sessionId
    ) {
        try {
            return $this->paymentTemplateApi->listPaymentTemplate(
                $memberId,
                BILLER::ALL_BILLERS,
                $paymentType,
                $sessionId
            );
        } catch (ApiException $exception) {
            throw new PaymentTemplateCodeApiException($exception, $exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * @param string $paymentTemplateId Payment template Id
     * @param string $sessionId         Session Id
     * @return InlineResponse404|InlineResponse500|RetrieveResponse
     * @throws PaymentTemplateDataNotFoundException
     * @throws RetrievePaymentTemplateException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function retrievePaymentTemplate(string $paymentTemplateId, string $sessionId)
    {
        try {
            return $this->paymentTemplateApi->retrievePaymentTemplate($paymentTemplateId, $sessionId);
        } catch (ApiException $exception) {
            switch ($exception->getCode()) {
                case Response::HTTP_NOT_FOUND:
                    throw new PaymentTemplateDataNotFoundException($paymentTemplateId);
                default:
                    throw new RetrievePaymentTemplateException();
            }
        }
    }

    /**
     * @param string $paymentTemplateId Payment template Id
     * @param string $lastFour          Last four
     * @param string $sessionId         Session Id
     * @return InlineResponse404|InlineResponse500|ValidatePaymentTemplateResponse
     * @throws InvalidPaymentTemplateLastFour
     * @throws RetrievePaymentTemplateException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function validatePaymentTemplate(string $paymentTemplateId, string $lastFour, string $sessionId)
    {
        try {
            return $this->paymentTemplateApi->validatePaymentTemplate($paymentTemplateId, $lastFour, $sessionId);
        } catch (ApiException $exception) {
            switch ($exception->getCode()) {
                case Response::HTTP_NOT_FOUND:
                    throw new InvalidPaymentTemplateLastFour();
                default:
                    throw new RetrievePaymentTemplateException();
            }
        }
    }
}
