<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProbillerNG\PaymentTemplateServiceClient\Api\PaymentTemplateCommandsApi;
use ProbillerNG\PaymentTemplateServiceClient\ApiException;
use ProbillerNG\PaymentTemplateServiceClient\Model\CreatePaymentTemplate;
use ProBillerNG\PurchaseGateway\Application\Services\CreatePaymentTemplateAsyncService;
use ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent\PaymentTemplateBillerFieldsFactory;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\InvalidBillerFieldsDataException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\CCTransactionInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;

class CreatePaymentTemplateService
{
    /**
     * @var PaymentTemplateCommandsApi
     */
    private $paymentTemplateCommandsApi;

    /**
     * @var TransactionService
     */
    private $transactionService;

    /**
     * @var CreatePaymentTemplateAsyncService
     */
    private $createPaymentTemplateAsyncService;

    /**
     * CreatePaymentTemplateService constructor.
     *
     * @param PaymentTemplateCommandsApi        $paymentTemplateCommandsApi
     * @param TransactionService                $transactionService
     * @param CreatePaymentTemplateAsyncService $createPaymentTemplateAsyncService
     */
    public function __construct(
        PaymentTemplateCommandsApi $paymentTemplateCommandsApi,
        TransactionService $transactionService,
        CreatePaymentTemplateAsyncService $createPaymentTemplateAsyncService
    ) {
        $this->paymentTemplateCommandsApi        = $paymentTemplateCommandsApi;
        $this->transactionService                = $transactionService;
        $this->createPaymentTemplateAsyncService = $createPaymentTemplateAsyncService;
    }

    /**
     * @param string|null        $memberId
     * @param TransactionId|null $transactionId
     * @param bool               $shouldCreateForNSF
     *
     * @throws Exception
     * @throws InvalidBillerFieldsDataException
     * @throws ApiException
     */
    public function addPaymentTemplate(?string $memberId, ?TransactionId $transactionId, bool $shouldCreateForNSF): void
    {
        try {
            Log::info('PaymentTemplateCreation start creating payment template.');

            if (empty($transactionId)) {
                Log::info('PaymentTemplateCreation Payment template not created, empty transactionId.');
                return;
            }

            if (empty($memberId)) {
                Log::info('PaymentTemplateCreation Payment template not created, empty MemberId.');
                return;
            }

            $transaction = $this->retrieveTransaction($transactionId);

            if (!$this->shouldCreatePaymentTemplate($transaction, $shouldCreateForNSF)) {
                Log::info('PaymentTemplateCreation Should not create payment template.');
                return;
            }

            $createPaymentTemplate = new CreatePaymentTemplate(
                [
                    'paymentType'     => 'cc',
                    'firstSix'        => $transaction->transactionInformation()->first6(),
                    'lastFour'        => $transaction->transactionInformation()->last4(),
                    'expirationMonth' => $transaction->transactionInformation()->cardExpirationMonth(),
                    'expirationYear'  => $transaction->transactionInformation()->cardExpirationYear(),
                    'billerFields'    => PaymentTemplateBillerFieldsFactory::create($transaction)
                ]
            );

            Log::info(
                'PaymentTemplateCreation Payment template request',
                [
                    'memberId'   => $memberId,
                    'billerName' => $transaction->billerName(),
                    'payload'    => $createPaymentTemplate
                ]
            );

            $paymentTemplateResponse = $this->paymentTemplateCommandsApi->createPaymentTemplate(
                $memberId,
                $transaction->billerName(),
                Log::getSessionId(),
                $createPaymentTemplate
            );

            Log::info(
                'PaymentTemplateCreation Payment template successfully created.',
                ['paymentTemplateResponse' => $paymentTemplateResponse]
            );
        } catch (ApiException $e) {
            Log::error(
                'PaymentTemplateCreation Payment template not created.',
                [
                    'message' => $e->getMessage(),
                    'code'    => $e->getCode()
                ]
            );

            Throw $e;
        }
    }

    /**
     * @param TransactionId $transactionId Transaction id
     * @return RetrieveTransactionResult
     * @throws Exception
     */
    private function retrieveTransaction(TransactionId $transactionId): ?RetrieveTransactionResult
    {
        try {
            Log::info(
                'PaymentTemplateCreation retrieving transaction.',
                ['transactionId' => (string) $transactionId]
            );
            return $this->transactionService->getTransactionDataBy(
                $transactionId,
                SessionId::createFromString(Log::getSessionId())
            );
        } catch (\Exception $e) {
            Log::error(
                'PaymentTemplateCreation Payment template not created, problem to retrieve transaction.',
                [
                    'message' => $e->getMessage(),
                    'code'    => $e->getCode()
                ]
            );
        }

        return null;
    }

    /**
     * @param RetrieveTransactionResult $transaction        Transaction
     * @param bool                      $shouldCreateForNSF Should create for nsf?
     * @return bool
     * @throws Exception
     */
    private function shouldCreatePaymentTemplate($transaction, bool $shouldCreateForNSF): bool
    {
        if (!$transaction instanceof RetrieveTransactionResult) {
            Log::info('PaymentTemplateCreation Payment template not created, problem to retrieve transaction.');
            return false;
        }

        if ($transaction->billerName() !== RocketgateBiller::BILLER_NAME
            && $transaction->billerName() !== NetbillingBiller::BILLER_NAME
        ) {
            Log::info(
                'PaymentTemplateCreation Payment template not created, biller not supported to create payment template.',
                ['billerName' => $transaction->billerName()]
            );
            return false;
        }

        if (!$transaction->transactionInformation() instanceof CCTransactionInformation) {
            Log::info(
                'PaymentTemplateCreation Payment template not created, transaction was not made with credit card to create a payment template.',
                ['Transaction type' => gettype($transaction->transactionInformation())]
            );
            return false;
        }

        if ($transaction->transactionInformation()->status() !== Transaction::STATUS_APPROVED) {
            if ($transaction->transactionInformation()->status() === Transaction::STATUS_DECLINED
                && $shouldCreateForNSF
            ) {
                Log::info(
                    'PaymentTemplateCreation transaction not approved, but we should create for NSF',
                    [
                        'Transaction status' => $transaction->transactionInformation()->status(),
                        'shouldCreateForNSF' => $shouldCreateForNSF,
                    ]
                );
                return true;
            }

            Log::info(
                'PaymentTemplateCreation Payment template not created, Transaction was not approved to create a payment template.',
                ['Transaction type' => get_class($transaction->transactionInformation())]
            );
            return false;
        }

        return true;
    }

    /**
     * @param TransactionId|null $transactionId
     * @param string|null        $purchaseId
     * @param string|null        $memberId
     *
     * @throws Exception
     */
    public function createPaymentTemplateAsyncEvent(
        ?TransactionId $transactionId,
        ?string $purchaseId,
        ?string $memberId
    ): void {
        try {
            $this->createPaymentTemplateAsyncService->create(
                (string) $transactionId,
                (string) $purchaseId,
                (string) $memberId
            );

            Log::info(
                "PaymentTemplateCreation Created async event",
                [
                    "sessionId"     => Log::getSessionId(),
                    "transactionId" => $transactionId,
                    "memberId"      => $memberId,
                    "purchaseId"    => $purchaseId,
                ]
            );
        } catch (\Exception $e) {
            Log::error(
                "PaymentTemplateCreation Could not create async event",
                [
                    "sessionId"     => Log::getSessionId(),
                    "transactionId" => $transactionId,
                    "memberId"      => $memberId,
                    "purchaseId"    => $purchaseId,
                    "message"       => $e->getMessage(),
                    "code"          => $e->getCode(),
                ]
            );
        }
    }
}
