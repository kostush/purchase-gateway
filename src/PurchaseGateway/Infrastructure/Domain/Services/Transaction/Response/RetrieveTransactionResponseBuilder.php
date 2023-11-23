<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\CCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\ChequePaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\OtherPaymentTypeInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\QyssoBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Services\BillerFactoryService;
use ProBillerNG\PurchaseGateway\Domain\Services\BillerFieldsFactoryService;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerIdException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\InvalidResponseException;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransaction;

class RetrieveTransactionResponseBuilder
{
    /**
     * @var array
     */
    private static $responseCCBillerMap = [
        RocketgateBiller::BILLER_ID => RocketgateCCRetrieveTransactionResult::class,
        NetbillingBiller::BILLER_ID => NetbillingCCRetrieveTransactionResult::class,
        EpochBiller::BILLER_ID      => EpochCCRetrieveTransactionResult::class,
    ];

    private static $responseOtherPaymentTypesBillerMap = [
        EpochBiller::BILLER_ID      => EpochOtherPaymentTypeRetrieveTransactionResult::class,
        QyssoBiller::BILLER_ID      => QyssoOtherPaymentTypeRetrieveTransactionResult::class,
        RocketgateBiller::BILLER_ID => RocketgateCheckRetrieveTransactionResult::class,
    ];

    /**
     * @param RetrieveTransaction $response Api response
     * @param bool|null           $isNsf    Is Nsf
     *
     * @return RetrieveTransactionResult
     * @throws InvalidResponseException
     * @throws Exception
     * @throws UnknownBillerIdException
     */
    public static function build(
        RetrieveTransaction $response,
        ?bool $isNsf
    ): RetrieveTransactionResult {
        $paymentType                 = $response->getPaymentType();
        $billerId                    = $response->getBillerId();
        $transactionInformationClass = self::getTransactionInformationClass($paymentType);

        if (!empty($transactionInformationClass)) {
            if ($transactionInformationClass === CCTransactionInformation::class) {
                if (empty($response->getTransaction()->getFirst6())) {
                    $transactionInformationClass = ExistingCCTransactionInformation::class;
                } else {
                    $transactionInformationClass = NewCCTransactionInformation::class;
                }
            }

            $transactionInformation = new $transactionInformationClass($response);
            $memberInformation      = new MemberInformation($response);
            $biller                 = BillerFactoryService::createFromBillerId($billerId);

            /** @var array $billerFieldsData */
            $billerFieldsData = $response->getBillerSettings();

            $billerFields = BillerFieldsFactoryService::create($biller, $billerFieldsData);

            $responseClass = self::getResponseClass($billerId, $paymentType);

            if (empty($responseClass)) {
                throw new InvalidResponseException('Cannot map response');
            }

            if (!is_null($isNsf)) {
                $transactionInformation->setIsNsf($isNsf);
            }

            return new $responseClass($response, $memberInformation, $transactionInformation, $billerFields);
        }

        throw new InvalidResponseException('Cannot map response');
    }

    /**
     * @param string      $billerId    Biller id
     * @param string|null $paymentType Payment type
     *
     * @return string
     */
    private static function getResponseClass(string $billerId, ?string $paymentType): string
    {
        if (empty($paymentType)) {
            return EmptyRetrieveTransactionResult::class;
        }

        if ($paymentType === CCPaymentInfo::PAYMENT_TYPE) {
            return self::$responseCCBillerMap[$billerId] ?? '';
        }

        if (in_array($paymentType, OtherPaymentTypeInfo::PAYMENT_TYPES)) {
            return self::$responseOtherPaymentTypesBillerMap[$billerId] ?? '';
        }

        return '';
    }

    /**
     * @param string|null $paymentType Payment type
     *
     * @return string
     */
    private static function getTransactionInformationClass(?string $paymentType): string
    {
        if (empty($paymentType)) {
            return EmptyTransactionInformation::class;
        }

        if (in_array($paymentType, OtherPaymentTypeInfo::PAYMENT_TYPES, true)) {
            if (ChequePaymentInfo::PAYMENT_TYPE === $paymentType) {
                return CheckTransactionInformation::class;
            }

            return OtherPaymentTypeTransactionInformation::class;
        }

        if (CCPaymentInfo::PAYMENT_TYPE === $paymentType) {
            return CCTransactionInformation::class;
        }

        return '';
    }
}
