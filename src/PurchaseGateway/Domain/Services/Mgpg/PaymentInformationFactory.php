<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services\Mgpg;

use ProbillerMGPG\Purchase\Process\PaymentInformation;
use ProbillerMGPG\Purchase\Process\PaymentInformation\CardHolderInfo;
use ProbillerMGPG\Purchase\Process\PaymentInformation\CardInformation;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\Services\Mgpg\NgResponseService;
use ProBillerNG\PurchaseGateway\Domain\Model\CCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\ChequePaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\ProcessPurchaseRequest;

class PaymentInformationFactory
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
     * @param ProcessPurchaseRequest $ngRequest
     * @return PaymentInformation
     * @throws \ProBillerNG\Logger\Exception
     */
    public function create(ProcessPurchaseRequest $ngRequest): PaymentInformation
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

        if ($this->ngResponseService->isCryptoPayment($ngRequest->paymentType(), $ngRequest->paymentMethod())) {
            Log::info('CreatingPaymentInformation Crypto payment: creating crypto information');
            return new PaymentInformation\CryptoInformation(
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
                $ngRequest->memberPassword(),
                $ngRequest->cryptoCurrency()
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
        return new CardInformation(
            $ngRequest->ccNumber(),
            $ngRequest->cvv(),
            $ngRequest->cardExpirationMonth(),
            $ngRequest->cardExpirationYear(),
            new CardHolderInfo(
                $ngRequest->memberEmail(),
                $ngRequest->memberCountryCode(),
                $ngRequest->memberZipCode(),
                $ngRequest->memberFirstName(),
                $ngRequest->memberLastName()
            )
        );
    }

    /**
     * @param ProcessPurchaseRequest $ngRequest
     *
     * @return array
     */
    public function getValidationParameter(ProcessPurchaseRequest $ngRequest): array
    {
        if ($ngRequest->paymentType() == CCPaymentInfo::PAYMENT_TYPE) {
            return [
                PaymentTemplate::IDENTITY_VERIFICATION_METHOD => $ngRequest->lastFour()
            ];
        }

        if ($ngRequest->paymentMethod() == ChequePaymentInfo::PAYMENT_METHOD) {
            return [
                PaymentTemplate::CHEQUE_IDENTITY_VERIFICATION_METHOD => $ngRequest->accountNumberLast4()
            ];
        }

        return [];
    }
}
