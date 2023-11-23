<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent;

use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\InvalidBillerFieldsDataException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\EpochBillerTransaction;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\EpochRetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\NetbillingCCRetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RocketgateCCRetrieveTransactionResult;

class PaymentTemplateBillerFieldsFactory
{
    /**
     * @param RetrieveTransactionResult $mainTransactionData
     * @return array
     * @throws InvalidBillerFieldsDataException
     * @throws \ProBillerNG\Logger\Exception
     */
    public static function create(RetrieveTransactionResult $mainTransactionData): array
    {
        $billerFields = null;
        switch ($mainTransactionData->billerName()) {
            case RocketgateBiller::BILLER_NAME:
                /** @var RocketgateCCRetrieveTransactionResult $mainTransactionData */
                $billerFields = [
                    'cardHash'           => $mainTransactionData->cardHash(),
                    'merchantCustomerId' => $mainTransactionData->customerId(),
                    'merchantId'         => $mainTransactionData->merchantId()
                ];

                break;
            case NetbillingBiller::BILLER_NAME:
                /** @var NetbillingCCRetrieveTransactionResult $mainTransactionData */
                $originId     = explode(":", base64_decode($mainTransactionData->cardHash()));
                $binRouting   = $mainTransactionData->billerFields()->binRouting();
                $billerFields = [
                    'originId'   => $originId[1],
                    'binRouting' => $binRouting,
                    'cardHash'   => $mainTransactionData->cardHash()
                ];
                break;
            case EpochBiller::BILLER_NAME:
                /** @var EpochRetrieveTransactionResult  $mainTransactionData */
                $billerTransaction = $mainTransactionData->billerTransactions()->last();
                /** @var EpochBillerTransaction $billerTransaction */
                $billerFields = [
                    'memberId'   => $billerTransaction->billerMemberId()
                ];

                break;
            default:
                throw new InvalidBillerFieldsDataException();
        }

        Log::info('PaymentTemplateCreation Payment template biller fields factory', ['billerFields'=> $billerFields]);

        return $billerFields;
    }
}
