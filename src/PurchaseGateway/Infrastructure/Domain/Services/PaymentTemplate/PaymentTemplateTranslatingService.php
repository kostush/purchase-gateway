<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate;

use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Domain\Services\PaymentTemplateTranslatingService as PaymentTranslatingServiceInterface;
use ProBillerNG\PurchaseGateway\Domain\Services\RetrievePaymentTemplateAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\RetrievePaymentTemplatesAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\ValidatePaymentTemplateAdapter;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerCollection;
use ProBillerNG\Logger\Exception;

class PaymentTemplateTranslatingService implements PaymentTranslatingServiceInterface
{
    /**
     * @var RetrievePaymentTemplatesAdapter
     */
    private $retrievePaymentTemplatesAdapter;

    /**
     * @var RetrievePaymentTemplateAdapter
     */
    private $retrievePaymentTemplateAdapter;

    /**
     * @var ValidatePaymentTemplateAdapter
     */
    private $validatePaymentTemplateAdapter;

    /**
     * PaymentTemplateTranslatingTranslatingService constructor.
     * @param RetrievePaymentTemplatesAdapter $retrievePaymentTemplatesAdapter Retrieve templates adapter
     * @param RetrievePaymentTemplateAdapter  $retrievePaymentTemplateAdapter  Retrieve template adapter
     * @param ValidatePaymentTemplateAdapter  $validatePaymentTemplateAdapter  Validate template adapter
     */
    public function __construct(
        RetrievePaymentTemplatesAdapter $retrievePaymentTemplatesAdapter,
        RetrievePaymentTemplateAdapter $retrievePaymentTemplateAdapter,
        ValidatePaymentTemplateAdapter $validatePaymentTemplateAdapter
    ) {
        $this->retrievePaymentTemplatesAdapter = $retrievePaymentTemplatesAdapter;
        $this->retrievePaymentTemplateAdapter  = $retrievePaymentTemplateAdapter;
        $this->validatePaymentTemplateAdapter  = $validatePaymentTemplateAdapter;
    }

    /**
     * @param string $memberId    Member Id
     * @param string $paymentType Payment type
     * @param string $sessionId   Session Id
     * @return PaymentTemplateCollection
     */
    public function retrieveAllPaymentTemplates(
        string $memberId,
        string $paymentType,
        string $sessionId
    ): PaymentTemplateCollection {
        return $this->retrievePaymentTemplatesAdapter->retrieveAllPaymentTemplates(
            $memberId,
            $paymentType,
            $sessionId
        );
    }

    /**
     * @param string $paymentTemplateId Payment template Id
     * @param string $sessionId         Session Id
     * @return PaymentTemplate
     */
    public function retrievePaymentTemplate(
        string $paymentTemplateId,
        string $sessionId
    ): PaymentTemplate {
        return $this->retrievePaymentTemplateAdapter->retrievePaymentTemplate(
            $paymentTemplateId,
            $sessionId
        );
    }

    /**
     * @param string $paymentTemplateId Payment template Id
     * @param string $lastFour          Last four
     * @param string $sessionId         Session Id
     * @return PaymentTemplate
     */
    public function validatePaymentTemplate(
        string $paymentTemplateId,
        string $lastFour,
        string $sessionId
    ): PaymentTemplate {
        return $this->validatePaymentTemplateAdapter->validatePaymentTemplate(
            $paymentTemplateId,
            $lastFour,
            $sessionId
        );
    }
}
