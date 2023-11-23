<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\Event;

use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\CannotCreateIntegrationEventException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\UnknownBillerIdException;
use ProBillerNG\PurchaseGateway\Domain\Model\Bundle;
use ProBillerNG\PurchaseGateway\Domain\Model\ChequePaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\CCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\OtherPaymentTypeInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\QyssoBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\QyssoRetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;

class PurchaseIntegrationEventBuilder
{
    /**
     * @var array
     */
    private static $eventMap = [
        CCPaymentInfo::PAYMENT_TYPE => [
            RocketgateBiller::BILLER_ID => RocketgateCCPurchaseImportEvent::class,
            NetbillingBiller::BILLER_ID => NetbillingCCPurchaseImportEvent::class,
            EpochBiller::BILLER_ID      => EpochCCPurchaseImportEvent::class
        ],
        ChequePaymentInfo::PAYMENT_TYPE => [
            RocketgateBiller::BILLER_ID => RocketgateCheckPurchaseImportEvent::class,
        ],
    ];

    /**
     * @var array
     */
    private static $itemMap = [
        RocketgateBiller::BILLER_ID => RocketgatePurchasedItemDetails::class,
        NetbillingBiller::BILLER_ID => NetbillingPurchasedItemDetails::class,
        EpochBiller::BILLER_ID      => EpochPurchasedItemDetails::class,
        QyssoBiller::BILLER_ID      => QyssoPurchasedItemDetails::class
    ];

    /**
     * @var array
     */
    private static $threeDSEventMap = [
        RocketgateBiller::BILLER_ID => RocketgateCC3DSPurchaseImportEvent::class,
    ];

    /**
     * @var array
     */
    private static $otherPaymentsEventMap = [
        EpochBiller::BILLER_ID => EpochOtherPaymentPurchaseImportEvent::class,
        QyssoBiller::BILLER_ID => QyssoDebitPurchaseImportEvent::class
    ];

    /**
     * @var array
     */
    private static $threeDS2EventMap = [
        RocketgateBiller::BILLER_ID => RocketgateCC3DS2PurchaseImportEvent::class,
    ];

    /**
     * @param RetrieveTransactionResult $retrieveTransactionResult Retrieve result
     * @param PurchaseProcessed         $purchaseProcessedEvent    Purchase details
     * @param PaymentTemplate|null      $paymentTemplateData       PaymentTemplate
     *
     * @return PurchaseEvent
     * @throws CannotCreateIntegrationEventException
     * @throws LoggerException
     * @throws \Exception
     */
    public static function build(
        RetrieveTransactionResult $retrieveTransactionResult,
        PurchaseProcessed $purchaseProcessedEvent,
        ?PaymentTemplate $paymentTemplateData = null
    ): PurchaseEvent {
        if ($retrieveTransactionResult instanceof QyssoRetrieveTransactionResult
            && $retrieveTransactionResult->type() === QyssoRetrieveTransactionResult::TYPE_REBILL
        ) {
            return new QyssoDebitRebillImportEvent($retrieveTransactionResult, $purchaseProcessedEvent);
        }

        $eventClass = self::getEventClass(
            $retrieveTransactionResult->paymentType(),
            $retrieveTransactionResult->billerId(),
            $retrieveTransactionResult->securedWithThreeD(),
            $retrieveTransactionResult->threeDSecureVersion()
        );

        if (!empty($eventClass)) {
            return new $eventClass(
                $retrieveTransactionResult,
                $purchaseProcessedEvent,
                $paymentTemplateData
            );
        }

        throw new CannotCreateIntegrationEventException;
    }

    /**
     * @param string   $paymentType         Payment type.
     * @param string   $billerId            Biller id.
     * @param bool     $securedWith3DS      The 3DS secured flag.
     * @param int|null $threeDSecureVersion ThreeD secure version.
     * @return string|null
     */
    private static function getEventClass(
        string $paymentType,
        string $billerId,
        bool $securedWith3DS,
        ?int $threeDSecureVersion
    ): ?string {
        if ($securedWith3DS === true) {
            if ($threeDSecureVersion === 2) {
                return self::$threeDS2EventMap[$billerId] ?? null;
            }
            return self::$threeDSEventMap[$billerId] ?? null;
        }

        if ($paymentType == CCPaymentInfo::PAYMENT_TYPE || $paymentType == ChequePaymentInfo::PAYMENT_TYPE) {
            return self::$eventMap[$paymentType][$billerId] ?? null;
        }

        if (in_array($paymentType, OtherPaymentTypeInfo::PAYMENT_TYPES)) {
            return self::$otherPaymentsEventMap[$billerId] ?? null;
        }

        return null;
    }

    /**
     * @param array                     $purchaseDetails           Purchase details
     * @param RetrieveTransactionResult $retrieveTransactionResult Retrieve result
     * @param Bundle                    $bundle                    Bundle
     * @param string|null               $parentSubscription        Parent subscription
     * @param Site|null                 $site                      site
     * @return PurchasedItemDetails
     * @throws CannotCreateIntegrationEventException
     * @throws LoggerException
     */
    public static function buildItem(
        array $purchaseDetails,
        RetrieveTransactionResult $retrieveTransactionResult,
        Bundle $bundle,
        ?string $parentSubscription = null,
        ?Site $site = null
    ): PurchasedItemDetails {
        $eventClass = self::getItemClass(
            $retrieveTransactionResult->billerId()
        );

        if (!empty($eventClass)) {
            return new $eventClass(
                $purchaseDetails,
                $retrieveTransactionResult,
                $bundle,
                $parentSubscription,
                $site
            );
        }

        throw new CannotCreateIntegrationEventException;
    }

    /**
     * @param string $billerId Biller id
     * @return string|null
     */
    private static function getItemClass(string $billerId): ?string
    {
        return self::$itemMap[$billerId] ?? null;
    }
}
