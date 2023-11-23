<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services\EmailTemplateService\Templates;

use DateInterval;
use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\ChequePaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\MemberInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\TransactionInformation;

class EmailTemplateGenericProbiller extends EmailTemplate
{
    public const DEFAULT_TAX_NAME = 'No taxes';
    public const DEFAULT_TAX_RATE = '0';
    // @todo remove this hardcoded template and if we dont receive template id from env file throw exception
    public const TEMPLATE_ID      = 'eeed8906-4c34-4ea8-89ee-445f3291b1a3';

    /**
     * @param Site                        $site                   Site
     * @param PurchaseProcessed           $purchaseProcessEvent   PurchaseProcessEvent
     * @param RetrieveTransactionResult   $transactionData        TransactionData
     * @param MemberInfo                  $memberInfo             Member Info
     * @param PaymentTemplate|null        $paymentTemplate        PaymentTemplate
     * @param array|null                  $crossSalePurchaseData  crossSalePurchaseData
     * @param TransactionInformation|null $transactionInformation TransactionInformation
     */
    protected function __construct(
        Site $site,
        PurchaseProcessed $purchaseProcessEvent,
        RetrieveTransactionResult $transactionData,
        MemberInfo $memberInfo,
        ?PaymentTemplate $paymentTemplate,
        ?array $crossSalePurchaseData,
        ?TransactionInformation $transactionInformation
    ) {
        parent::__construct(
            $site,
            $purchaseProcessEvent,
            $transactionData,
            $memberInfo,
            $paymentTemplate,
            $crossSalePurchaseData,
            $transactionInformation
        );

        // @todo remove this hardcoded template and if we dont receive template id from env file throw exception
        $this->templateId = env('EMAIL_SERVICE_TEMPLATE_ID', self::TEMPLATE_ID);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function templateData(): array
    {
        $taxes = $this->getTransactionAmountFromEvent(
            $this->purchaseProcessedEvent,
            $this->transactionData->transactionInformation()->transactionId()
        );

        // Conversion to % based on new requirement confirmed on ticket BG-50364
        $taxRate =  $taxes['taxRate'] ?? self::DEFAULT_TAX_RATE;
        $taxRate = number_format( $taxRate * 100, 2 );

        $data = [
            "SITE_NAME"                 => $this->site->name(),
            "DESCRIPTOR_NAME"           => $this->site->descriptor(),
            "CURRENCYSYMBOL"            => CurrencyCode::symbolByCode($this->transactionData->currency()),
            "CURRENCY"                  => $this->transactionData->currency(),
            "REMOTE_ADDR"               => $this->purchaseProcessedEvent->ipAddress(),
            "TAXNAME"                   => $taxes['taxName'] ?? self::DEFAULT_TAX_NAME,
            "TAXRATE"                   => (string) $taxRate . '%',
            "online_support_link"       => $this->site->supportLink(),
            "support_cancellation_link" => $this->site->cancellationLink(),
            "call_support_link"         => 'tel:' . $this->site->phoneNumber(),
            "mail_support_link"         => 'mailto:' . $this->site->mailSupportLink(),
            "message_support_link"      => $this->site->messageSupportLink(),
            "skype_support_link"        => $this->site->skypeNumber(),
        ];

        if (!empty($this->purchaseProcessedEvent->paymentMethod())) {
            $data["PAYMENT_METHOD"] = (string) $this->purchaseProcessedEvent->paymentMethod();
        }

        //Checks email body doesn't have credit card number
        if ($this->transactionData->paymentType() === ChequePaymentInfo::PAYMENT_TYPE) {
            $data["ACCT_NUMBER_XXX"] = $this->returnAccountNumber();
        } else {
            $data["CARD_NUMBER_XXX"] = $this->createMaskedCreditCardForCustomerEmail();
        }

        if (!empty($this->memberInfo->firstName())
            && !empty($this->memberInfo->lastName())
        ) {
            $data = array_merge(
                $data,
                [
                    "REAL_NAME1" => $this->memberInfo->firstName(),
                    "REAL_NAME2" => $this->memberInfo->lastName(),
                ]
            );
        }

        if (!empty($this->memberInfo->username())
            && !empty($this->site->url())
        ) {
            $data = array_merge(
                $data,
                [
                    "LOGIN_NAME" => (string) $this->memberInfo->username(),
                    "SITE_URL"   => $this->site->url(),
                ]
            );
        }

        if ($this->transactionData->transactionInformation()->amount() != 0) {
            $data = array_merge(
                $data,
                [
                    "TRANSACTION_DATE" => (string) $this->purchaseProcessedEvent->occurredOn()
                        ->format('Y-m-d'),
                    "AMOUNTPRETAX"     => (string) $taxes['initialAmount']['beforeTaxes'],
                    "AMOUNTTAXONLY"    => (string) $taxes['initialAmount']['taxes'],
                    "AMOUNTWITHTAX"    => (string) $taxes['initialAmount']['afterTaxes']
                ]
            );
        }



        if (!empty($taxes['rebillAmount'])) {
            $initialDays = $this->purchaseProcessedEvent->initialDays();

            // Email is for cross sale
            if (!empty($this->crossSalePurchaseData)) {
                if (isset($this->crossSalePurchaseData['initialDays'])) {
                    $initialDays = $this->crossSalePurchaseData['initialDays'];
                } else {
                    Log::warning('EmailTemplate could not find crossSale initialDays.');
                }
            }

            $data = array_merge(
                $data,
                [
                    "REBILLING_DUE_DATE"            => (string) $this->purchaseProcessedEvent->occurredOn()
                        ->add(new DateInterval('P' . $initialDays . 'D'))
                        ->format('Y-m-d'),
                    "NEXT_REBILLING_AMOUNT_PRETAX"  => (string) $taxes['rebillAmount']['beforeTaxes'],
                    "NEXT_REBILLING_AMOUNT_TAXONLY" => (string) $taxes['rebillAmount']['taxes'],
                    "NEXT_REBILLING_AMOUNT_WITHTAX" => (string) $taxes['rebillAmount']['afterTaxes'],
                ]
            );

            if (!empty($this->transactionData->transactionInformation()->rebillFrequency())) {
                $data['REBILL_DAYS'] = (string) $this->transactionData->transactionInformation()->rebillFrequency();
            }
        }

        if (!empty($this->crossSalePurchaseData)) {
            $data["INITIAL_DAYS"] = (string) $this->crossSalePurchaseData['initialDays'];
        } else {
            $data["INITIAL_DAYS"] = (string) $this->purchaseProcessedEvent->initialDays();
        }

        return $data;
    }

    /**
     * @return string
     */
    protected function createMaskedCreditCardForCustomerEmail() : string
    {
        return ($this->getFirst6() ?? 'XXXXXX') . 'XXXXXX' . ($this->getLast4() ?? 'XXXX');
    }

    /**
     * @return string
     * @throws Exception
     */
    private function returnAccountNumber(): string
    {
        if (!isset($this->purchaseProcessedEvent->payment()['accountNumber'])) {
            Log::warning('EmailTemplate could not find the account number for check purchase.');
            return 'Not informed.';
        }

        return $this->purchaseProcessedEvent->payment()['accountNumber'];
    }

    /**
     * @param PurchaseProcessed $event         Event
     * @param string            $transactionId TransactionId
     * @return array
     */
    private function getTransactionAmountFromEvent(PurchaseProcessed $event, string $transactionId): array
    {
        if ($event->lastTransactionId() == $transactionId) {
            return $event->amounts() ?? $this->generateDefaultTaxData($event->amount(), $event->rebillAmount());
        }
        foreach ($event->crossSalePurchaseData() as $crossSalesPurchaseData) {
            if (end($crossSalesPurchaseData['transactionCollection'])['transactionId'] == $transactionId) {
                return $crossSalesPurchaseData['tax'] ?? $this->generateDefaultTaxData(
                    $crossSalesPurchaseData['initialAmount'],
                    $crossSalesPurchaseData['rebillAmount']
                );
            }
        }
    }

    /**
     * @param float      $amount       Amount
     * @param float|null $rebillAmount Rebill amount
     * @return array
     */
    private function generateDefaultTaxData(float $amount, ?float $rebillAmount): array
    {
        $amountData = [
            'taxName'       => 'No taxes',
            'taxRate'       => '0',
            'initialAmount' => [
                'beforeTaxes' => $amount,
                'afterTaxes'  => $amount,
                'taxes'       => '0'
            ]
        ];

        if (!is_null($rebillAmount)) {
            $amountData['rebillAmount'] = [
                'beforeTaxes' => $rebillAmount,
                'afterTaxes'  => $rebillAmount,
                'taxes'       => '0'
            ];
        }

        return $amountData;
    }

    /**
     * @return string|null
     * @throws Exception
     */
    private function getFirst6() : ?string
    {
        // try to get it from transactionData
        $first6 = $this->transactionData->transactionInformation()->first6() ?? null;

        if (!empty($first6)) {
            Log::info(
                "EmailTemplate first6 set from transaction data ",
                [
                    'first6'    => $first6,
                    'sessionId' => $this->purchaseProcessedEvent->sessionId()
                ]
            );

            return $first6;
        }

        if ($this->paymentTemplate instanceof PaymentTemplate) {
            // try to get it from paymentTemplate
            $first6 = $this->paymentTemplate->firstSix() ?? null;

            if (!empty($first6)) {
                Log::info(
                    "EmailTemplate first6 set from payment template",
                    [
                        'first6'    => $first6,
                        'sessionId' => $this->purchaseProcessedEvent->sessionId()
                    ]
                );

                return $first6;
            }
        }

        // try to get it from purchaseProcessedEvent
        $first6 = $this->purchaseProcessedEvent->payment()['first6'] ?? null;

        if (!empty($first6)) {
            Log::info(
                "EmailTemplate first6 set from payment ",
                [
                    'first6'    => $first6,
                    'sessionId' => $this->purchaseProcessedEvent->sessionId(),
                ]
            );

            return $first6;
        }

        //if all else fails, for cross sale, try to get it from the transactionInformation of the main purchase
        if (!empty($this->transactionInformation)) {
            $first6 = $this->transactionInformation->first6();

            Log::info(
                "EmailTemplate first6 set from transaction information",
                [
                    'first6'    => $first6,
                    'sessionId' => $this->purchaseProcessedEvent->sessionId()
                ]
            );
        } else {
            Log::info("EmailTemplate Transaction information is empty");
        }

        if (is_null($first6)) {
            Log::info(
                "EmailTemplate Could not determine first 6 CC's digits, when sending email.",
                [
                    'sessionId' => $this->purchaseProcessedEvent->sessionId()
                ]
            );
            Log::warning("Could not determine first 6 CC's digits, when sending email.");
        }

        return $first6;
    }

    /**
     * @return string|null
     * @throws Exception
     */
    private function getLast4() : ?string
    {
        // try to get it from transactionData
        $last4 = $this->transactionData->transactionInformation()->last4() ?? null;

        if (!empty($last4)) {

            Log::info(
                "EmailTemplate last4 set from transaction information",
                [
                    'last4'     => $last4,
                    'sessionId' => $this->purchaseProcessedEvent->sessionId()
                ]
            );

            return $last4;
        }

        if ($this->paymentTemplate instanceof PaymentTemplate) {
            // try to get it from paymentTemplate
            $last4 = $this->paymentTemplate->lastFour() ?? null;

            if (!empty($last4)) {

                Log::info(
                    "EmailTemplate last4 set from payment template",
                    [
                        'last4'     => $last4,
                        'sessionId' => $this->purchaseProcessedEvent->sessionId()
                    ]
                );

                return $last4;
            }
        }

        // try to get it from purchaseProcessedEvent
        $last4 = $this->purchaseProcessedEvent->payment()['last4'] ?? null;

        if (!empty($last4)) {

            Log::info(
                "EmailTemplate last4 set from payment",
                [
                    'last4'     => $last4,
                    'sessionId' => $this->purchaseProcessedEvent->sessionId()
                ]
            );

            return $last4;
        }

        //if all else fails, for cross sale, try to get it from the transactionInformation of the main purchase
        if (!empty($this->transactionInformation)) {

            $last4 = $this->transactionInformation->last4();

            Log::info(
                "EmailTemplate last4 set from transaction information",
                [
                    'last4'     => $last4,
                    'sessionId' => $this->purchaseProcessedEvent->sessionId()
                ]
            );
        }

        if (is_null($last4)) {
            Log::info(
                "EmailTemplate Could not determine last 4 CC's digits, when sending email.",
                [
                    'sessionId' => $this->purchaseProcessedEvent->sessionId()
                ]
            );

            Log::warning("Could not determine last 4 CC's digits, when sending email.");
        }

        return $last4;
    }
}
