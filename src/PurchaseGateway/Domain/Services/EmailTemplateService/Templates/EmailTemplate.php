<?php

namespace ProBillerNG\PurchaseGateway\Domain\Services\EmailTemplateService\Templates;

use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\MemberInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\TransactionInformation;

abstract class EmailTemplate
{

    /** @var string */
    protected $templateId;

    /** @var Site */
    protected $site;

    /** @var PurchaseProcessed */
    protected $purchaseProcessedEvent;

    /** @var RetrieveTransactionResult */
    protected $transactionData;

    /** @var MemberInfo */
    protected $memberInfo;

    /** @var PaymentTemplate */
    protected $paymentTemplate;

    /** @var array $crossSalePurchaseData */
    protected $crossSalePurchaseData;

    /**
     * @var TransactionInformation|null TransactionInformation from the main purchase
     */
    protected $transactionInformation;

    /**
     * EmailTemplate constructor.
     *
     * @param Site                        $site                   Site
     * @param PurchaseProcessed           $purchaseProcessEvent   PurchaseProcessEvent
     * @param RetrieveTransactionResult   $transactionData        TransactionData
     * @param MemberInfo                  $memberInfo             MemberInfo
     * @param PaymentTemplate|null        $paymentTemplate        PaymentTemplate
     * @param array|null                  $crossSalePurchaseData  CrossSalePurchaseData
     * @param TransactionInformation|null $transactionInformation TransactionInformation
     */
    protected function __construct(
        Site $site,
        PurchaseProcessed $purchaseProcessEvent,
        RetrieveTransactionResult $transactionData,
        MemberInfo $memberInfo,
        ?PaymentTemplate $paymentTemplate,
        ?array $crossSalePurchaseData = null,
        ?TransactionInformation $transactionInformation = null
    ) {
        $this->site                   = $site;
        $this->purchaseProcessedEvent = $purchaseProcessEvent;
        $this->transactionData        = $transactionData;
        $this->memberInfo             = $memberInfo;
        $this->paymentTemplate        = $paymentTemplate;
        $this->crossSalePurchaseData  = $crossSalePurchaseData;
        $this->transactionInformation = $transactionInformation;
    }

    /**
     * @param Site                        $site                   Site
     * @param PurchaseProcessed           $purchaseProcessEvent   PurchaseProcessEvent
     * @param RetrieveTransactionResult   $transactionData        TransactionData
     * @param MemberInfo                  $memberInfo             MemberInfo
     * @param PaymentTemplate|null        $paymentTemplate        PaymentTemplate
     * @param TransactionInformation|null $transactionInformation TransactionInformation
     * @param array|null                  $crossSalePurchaseData  CrossSalePurchaseData
     *
     * @return EmailTemplate
     */
    public static function getTemplate(
        Site $site,
        PurchaseProcessed $purchaseProcessEvent,
        RetrieveTransactionResult $transactionData,
        MemberInfo $memberInfo,
        ?PaymentTemplate $paymentTemplate,
        ?array $crossSalePurchaseData = null,
        ?TransactionInformation $transactionInformation = null
    ): EmailTemplate {
        return new static($site, $purchaseProcessEvent, $transactionData, $memberInfo, $paymentTemplate, $crossSalePurchaseData, $transactionInformation);
    }

    /**
     * @return string
     */
    public function templateId(): string
    {
        return $this->templateId;
    }

    /**
     * @return array
     */
    abstract public function templateData(): array;
}
