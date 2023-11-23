<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidPaymentTemplateId;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidPaymentTemplateLastFour;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;

class PaymentTemplateService
{
    private const LAST_FOUR_LENGTH = 4;

    /**
     * @var PaymentTemplateTranslatingService
     */
    private $paymentTemplateTranslatingService;

    /**
     * @var PurchaseProcess
     */
    private $purchaseProcess;

    /**
     * PaymentTemplateService constructor.
     * @param PaymentTemplateTranslatingService $paymentTemplateTranslatingService PaymentTemplateTranslatingService
     */
    public function __construct(PaymentTemplateTranslatingService $paymentTemplateTranslatingService)
    {
        $this->paymentTemplateTranslatingService = $paymentTemplateTranslatingService;
    }

    /**
     * @param PurchaseProcess $purchaseProcess            Purchase Process
     * @param array           $paymentTemplateInformation Payment Template Information
     * @return PaymentTemplate
     * @throws InvalidPaymentTemplateId
     * @throws InvalidPaymentTemplateLastFour
     * @throws Exception
     */
    public function retrievePaymentTemplate(
        PurchaseProcess $purchaseProcess,
        array $paymentTemplateInformation
    ): PaymentTemplate {
        $this->purchaseProcess = $purchaseProcess;

        $paymentTemplate = $this->purchaseProcess->paymentTemplateCollection()
            ->get($paymentTemplateInformation['paymentTemplateId']);

        if (!$paymentTemplate instanceof PaymentTemplate) {
            throw new InvalidPaymentTemplateId();
        }

        if ($paymentTemplate->isSafe()) {
            $paymentTemplate = $this->paymentTemplateTranslatingService->retrievePaymentTemplate(
                $paymentTemplateInformation['paymentTemplateId'],
                (string) $this->purchaseProcess->sessionId()
            );
            $paymentTemplate->setIsSelected(true);
            $paymentTemplate->setIsSafe(true);

            $this->purchaseProcess->paymentTemplateCollection()->offsetSet(
                $paymentTemplate->templateId(),
                $paymentTemplate
            );

            return $paymentTemplate;
        }

        if (empty($paymentTemplateInformation['lastFour'])
            || !is_numeric($paymentTemplateInformation['lastFour'])
            || strlen($paymentTemplateInformation['lastFour']) !== static::LAST_FOUR_LENGTH
        ) {
            throw new InvalidPaymentTemplateLastFour();
        }

        $paymentTemplate = $this->paymentTemplateTranslatingService->validatePaymentTemplate(
            $paymentTemplateInformation['paymentTemplateId'],
            $paymentTemplateInformation['lastFour'],
            (string) $this->purchaseProcess->sessionId()
        );

        $paymentTemplate->setIsSelected(true);

        $this->purchaseProcess->paymentTemplateCollection()->offsetSet(
            $paymentTemplate->templateId(),
            $paymentTemplate
        );

        return $paymentTemplate;
    }

    /**
     * @param PurchaseProcess $purchaseProcess            Purchase Process
     * @param array           $paymentTemplateInformation Payment Template Information
     * @return PaymentTemplate
     * @throws InvalidPaymentTemplateId
     * @throws Exception
     */
    public function retrieveThirdPartyBillerPaymentTemplate(
        PurchaseProcess $purchaseProcess,
        array $paymentTemplateInformation
    ): PaymentTemplate {
        $this->purchaseProcess = $purchaseProcess;

        $paymentTemplate = $this->purchaseProcess->paymentTemplateCollection()
            ->get($paymentTemplateInformation['paymentTemplateId']);

        if (!$paymentTemplate instanceof PaymentTemplate) {
            throw new InvalidPaymentTemplateId();
        }

        $paymentTemplate = $this->paymentTemplateTranslatingService->retrievePaymentTemplate(
            $paymentTemplateInformation['paymentTemplateId'],
            (string) $this->purchaseProcess->sessionId()
        );
        $paymentTemplate->setIsSelected(true);

        $this->purchaseProcess->paymentTemplateCollection()->offsetSet(
            $paymentTemplate->templateId(),
            $paymentTemplate
        );

        return $paymentTemplate;
    }
}
