<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services\EmailTemplateService;

use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\MemberInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Services\EmailTemplateService\Templates\EmailTemplate;
use ProBillerNG\PurchaseGateway\Domain\Services\EmailTemplateService\Templates\EmailTemplateGenericProbiller;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\TransactionInformation;

class EmailTemplateService
{
    /**
     * @param Site                        $site                   Site
     * @param PurchaseProcessed           $purchaseProcessEvent   PurchaseProcessEvent
     * @param RetrieveTransactionResult   $transactionData        TransactionData
     * @param MemberInfo                  $memberInfo             MemberInfo
     * @param PaymentTemplate|null        $paymentTemplate        PaymentTemplate
     * @param array|null                  $crossSalePurchaseData  CrossSalePurchaseData
     * @param TransactionInformation|null $transactionInformation TransactionInformation
     *
     * @return EmailTemplate
     */
    public function getTemplate(
        Site $site,
        PurchaseProcessed $purchaseProcessEvent,
        RetrieveTransactionResult $transactionData,
        MemberInfo $memberInfo,
        ?PaymentTemplate $paymentTemplate,
        ?array $crossSalePurchaseData,
        ?TransactionInformation $transactionInformation
    ): EmailTemplate {
        return EmailTemplateGenericProbiller::getTemplate(
            $site,
            $purchaseProcessEvent,
            $transactionData,
            $memberInfo,
            $paymentTemplate,
            $crossSalePurchaseData,
            $transactionInformation
        );
    }
}
