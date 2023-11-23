<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services\Mgpg;

use ProbillerMGPG\SubsequentOperations\Process\PaymentInformation;
use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\Services\Mgpg\NgResponseService;
use ProBillerNG\PurchaseGateway\Domain\Model\ChequePaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\Mgpg\ProcessRebillUpdateRequest;

class RebillUpdatePaymentInformationFactory
{
    /**
     * @var NgResponseService
     */
    private $ngResponseService;

    public function __construct(NgResponseService $ngResponseService)
    {
        $this->ngResponseService = $ngResponseService;
    }

    /**
     * @param ProcessRebillUpdateRequest $ngRequest
     * @return PaymentInformation
     * @throws Exception
     */
    public function create(ProcessRebillUpdateRequest $ngRequest): PaymentInformation
    {
        if (isset($ngRequest->payment()['paymentTemplateInformation'])) {
            Log::info('CreatingPaymentInformation Creating payment template information.');
            return new PaymentInformation\PaymentTemplateInformation(
                $ngRequest->paymentTemplateId(),
                $this->getValidationParameter($ngRequest)
            );
        }

        if ($this->ngResponseService->isHybridPayment($ngRequest->paymentType(), $ngRequest->paymentMethod())) {
            Log::info('CreatingPaymentInformation Hybrid payment: creating user information');
            return new PaymentInformation\User(
                $ngRequest->memberEmail(),
                $ngRequest->memberCountryCode(),
                $ngRequest->memberZipCode(),
                $ngRequest->memberFirstName(),
                $ngRequest->memberLastName(),
                $ngRequest->memberAddress1(),
                $ngRequest->memberCity(),
                $ngRequest->memberPhone(),
                $ngRequest->memberState(),
                $ngRequest->memberUsername(),
                $ngRequest->memberPassword()
            );
        }

        if (isset($ngRequest->payment()['checkInformation'])) {
            Log::info('CreatingPaymentInformation Creating Check information.');
            return new PaymentInformation\CheckInformation(
                $ngRequest->routingNumber(),
                $ngRequest->accountNumber(),
                $ngRequest->socialSecurityLast4(),
                $ngRequest->savingAccount(),
                $ngRequest->label()
            );
        }

        Log::info('CreatingPaymentInformation Creating card information.');
        return new PaymentInformation\CardInformation(
            $ngRequest->ccNumber(),
            $ngRequest->cvv(),
            $ngRequest->cardExpirationMonth(),
            $ngRequest->cardExpirationYear(),
            new PaymentInformation\CardHolderInfo(
                $ngRequest->memberEmail(),
                $ngRequest->memberCountryCode(),
                $ngRequest->memberZipCode(),
                $ngRequest->memberFirstName(),
                $ngRequest->memberLastName()
            )
        );
    }

    /**
     * @param ProcessRebillUpdateRequest $ngRequest
     *
     * @return array
     */
    public function getValidationParameter(ProcessRebillUpdateRequest $ngRequest): array
    {
        $validationParameter = [
            PaymentTemplate::IDENTITY_VERIFICATION_METHOD => $ngRequest->lastFour()
        ];

        if ($ngRequest->paymentMethod() == ChequePaymentInfo::PAYMENT_METHOD) {
            $validationParameter = [
                PaymentTemplate::CHEQUE_IDENTITY_VERIFICATION_METHOD => $ngRequest->accountNumberLast4()
            ];
        }

        return $validationParameter;
    }
}
