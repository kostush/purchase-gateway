<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\VoidTransactions;

use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\Projection\Application\Service\BaseTrackingWorkerHandler;
use ProBillerNG\Projection\Domain\ItemSourceBuilder;
use ProBillerNG\Projection\Domain\ItemToWorkOn;
use ProBillerNG\Projection\Domain\Projectionist\Projectionist;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\Id;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\Publisher;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\PaymentTemplateTranslatingService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\TransactionInformation;

class VoidTransactionsCommandHandler extends BaseTrackingWorkerHandler
{
    public const WORKER_NAME  = 'void-transactions';
    public const PAYMENT_KEYS = ['ccNumber', 'paymentTemplateId', 'first6'];

    /**
     * @var TransactionService
     */
    private $transactionService;

    /**
     * @var PaymentTemplateTranslatingService
     */
    private $paymentTemplateService;

    /**
     * @var Publisher
     */
    private $publisher;

    /**
     * @var Id|SessionId
     */
    private $requestSessionId;

    /**
     * VoidTransactionsCommandHandler constructor.
     * @param Projectionist                     $projectionist          Projectionist
     * @param ItemSourceBuilder                 $itemSourceBuilder      Item source builder
     * @param TransactionService                $transactionService     Transaction Service
     * @param PaymentTemplateTranslatingService $paymentTemplateService Payment Template Service
     * @param Publisher                         $publisher              Publisher of messages
     * @throws \Exception
     */
    public function __construct(
        Projectionist $projectionist,
        ItemSourceBuilder $itemSourceBuilder,
        TransactionService $transactionService,
        PaymentTemplateTranslatingService $paymentTemplateService,
        Publisher $publisher
    ) {
        parent::__construct($projectionist, $itemSourceBuilder);

        $this->transactionService     = $transactionService;
        $this->paymentTemplateService = $paymentTemplateService;
        $this->publisher              = $publisher;
        $this->requestSessionId       = SessionId::createFromString(Log::getSessionId());
    }

    /**
     * @param ItemToWorkOn $item Item
     * @return void
     * @throws Exception
     */
    protected function operation(ItemToWorkOn $item): void
    {
        try {
            if (!$this->isVoidTransactionEnabled()) {
                return;
            }

            $purchaseProcessedEvent = PurchaseProcessed::createFromJson($item->body());

            if ($purchaseProcessedEvent->lastTransaction()['state'] !== Transaction::STATUS_APPROVED
                || $purchaseProcessedEvent->skipVoidTransaction()
                || empty(array_intersect_key($purchaseProcessedEvent->payment(), array_flip(self::PAYMENT_KEYS)))
            ) {
                Log::info(
                    'Skipping voiding of PurchaseProcessed',
                    [
                        'sessionId'            => $purchaseProcessedEvent->sessionId(),
                        'transactionId'        => $purchaseProcessedEvent->lastTransactionId(),
                        'lastTransactionState' => $purchaseProcessedEvent->lastTransaction()['state'],
                        'payment'              => $purchaseProcessedEvent->payment(),
                        'skipVoidTransaction'  => $purchaseProcessedEvent->skipVoidTransaction()
                    ]
                );

                return;
            }

            $this->handleVoidTransactions($purchaseProcessedEvent);
        } catch (\Exception $exception) {
            Log::logException($exception);
        }
    }

    /**
     * @return bool
     */
    private function isVoidTransactionEnabled(): bool
    {
        return filter_var(
            env('ENABLE_VOID_TRANSACTION_FEATURE', false),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    /**
     * @param PurchaseProcessed $purchaseProcessedEvent Purchase processed event
     * @return void
     * @throws Exception
     * @throws \Exception
     */
    private function handleVoidTransactions(PurchaseProcessed $purchaseProcessedEvent): void
    {
        Log::info(
            'Voiding transactions for given PurchaseProcessed',
            $purchaseProcessedEvent->toArray()
        );

        $paymentTemplate = $this->retrievePaymentTemplate($purchaseProcessedEvent->payment());

        $this->voidTransaction($purchaseProcessedEvent->lastTransactionId(), $paymentTemplate);

        foreach ($purchaseProcessedEvent->crossSalePurchaseData() as $crossSale) {
            if (!isset($crossSale['transactionCollection'])) {
                continue;
            }

            $lastTransaction = end($crossSale['transactionCollection']);

            if (!isset($lastTransaction['transactionId'])) {
                continue;
            }

            $this->voidTransaction($lastTransaction['transactionId'], $paymentTemplate);
        }
    }

    /**
     * @param array $payment Payment
     * @return PaymentTemplate|null
     * @throws Exception
     */
    private function retrievePaymentTemplate(array $payment): ?PaymentTemplate
    {
        if (!isset($payment['paymentTemplateId'])) {
            return null;
        }

        Log::info('Retrieving payment template: ' . $payment['paymentTemplateId']);

        return $this->paymentTemplateService->retrievePaymentTemplate(
            $payment['paymentTemplateId'],
            (string) $this->requestSessionId
        );
    }

    /**
     * @param string               $transactionId   Transaction id
     * @param PaymentTemplate|null $paymentTemplate Payment template
     * @return void
     * @throws Exception
     * @throws \Exception
     */
    private function voidTransaction(string $transactionId, ?PaymentTemplate $paymentTemplate): void
    {
        Log::info(
            'Retrieving data from transaction',
            ['transactionId' => $transactionId]
        );

        $transaction = $this->transactionService->getTransactionDataBy(
            TransactionId::createFromString($transactionId),
            $this->requestSessionId
        );

        Log::info(
            'Creating biller fields, subsequent operation and payment information',
            ['transactionId' => $transactionId]
        );

        $billerFields        = $this->createBillerFields($transaction->billerFields());
        $subsequentOperation = $this->createSubsequentOperation($transaction);
        $paymentInformation  = $this->createPaymentInformation($transaction->transactionInformation(), $paymentTemplate);

        Log::info(
            'Publishing transaction for void action',
            ['transactionId' => $transactionId]
        );

        $this->publisher->publishTransactionToBeVoided(
            $transactionId,
            $transaction->siteId(),
            $billerFields,
            $subsequentOperation,
            $paymentInformation,
            $this->requestSessionId
        );

        Log::info(
            'Finished publishing transaction',
            ['transactionId' => $transactionId]
        );
    }

    /**
     * @param BillerFields $billerFields Biller fields
     * @return array
     */
    private function createBillerFields(BillerFields $billerFields): array
    {
        switch (get_class($billerFields)) {
            case RocketgateBillerFields::class:
                return [
                    'rocketgate' => [
                        'merchantId'       => $billerFields->merchantId(),
                        'merchantPassword' => $billerFields->merchantPassword(),
                        'merchantSiteId'   => $billerFields->billerSiteId(),
                    ]
                ];
            case NetbillingBillerFields::class:
                return [
                    'netbilling' => [
                        'accountId'        => $billerFields->accountId(),
                        'merchantPassword' => $billerFields->merchantPassword(),
                        'siteTag'          => $billerFields->siteTag(),
                    ]
                ];
            default:
                return [];
        }
    }

    /**
     * @param RetrieveTransactionResult $transaction Retrieved transaction
     * @return array
     */
    private function createSubsequentOperation(RetrieveTransactionResult $transaction): array
    {
        $lastBillerTransaction = $transaction->billerTransactions()->last();

        switch ($transaction->billerName()) {
            case RocketgateBiller::BILLER_NAME:
                return [
                    'subsequentOperationFields' => [
                        'rocketgate' => [
                            'merchantAccount'    => $transaction->merchantAccount(),
                            'merchantCustomerId' => $transaction->billerFields()->merchantCustomerId(),
                            'merchantInvoiceId'  => $transaction->billerFields()->merchantInvoiceId(),
                            'referenceGuid'      => $lastBillerTransaction->getBillerTransactionId(),
                        ]
                    ]
                ];
            case NetbillingBiller::BILLER_NAME:
                return [
                    'subsequentOperationFields' => [
                        'netbilling' => [
                            'billerMemberId' => $transaction->billerMemberId(),
                            'transId'        => $lastBillerTransaction->getBillerTransactionId(),
                        ]
                    ]
                ];
            default:
                return [];
        }
    }

    /**
     * @param TransactionInformation $transactionInformation Transaction information
     * @param PaymentTemplate|null   $paymentTemplate        Payment template
     * @return array
     */
    private function createPaymentInformation(
        TransactionInformation $transactionInformation,
        ?PaymentTemplate $paymentTemplate
    ): array {
        if (null !== $paymentTemplate) {
            return [
                'first6'              => $paymentTemplate->firstSix(),
                'last4'               => $paymentTemplate->lastFour(),
                'cardExpirationMonth' => $paymentTemplate->expirationMonth(),
                'cardExpirationYear'  => $paymentTemplate->expirationYear(),
            ];
        }

        return [
            'first6'              => $transactionInformation->first6(),
            'last4'               => $transactionInformation->last4(),
            'cardExpirationMonth' => $transactionInformation->cardExpirationMonth(),
            'cardExpirationYear'  => $transactionInformation->cardExpirationYear(),
        ];
    }
}
