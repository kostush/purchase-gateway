<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\Event;

use ProBillerNG\PurchaseGateway\Domain\Model\Bundle;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\EpochCCRetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\EpochRetrieveTransactionResult;

class EpochPurchasedItemDetails extends PurchasedItemDetails
{
    /**
     * @var array array
     */
    protected $billerTransactions = [];

    /**
     * RocketgatePurchasedItemDetails constructor.
     * @param array                            $purchaseDetails           Purchase details
     * @param EpochCCRetrieveTransactionResult $retrieveTransactionResult Retrieve result
     * @param Bundle                           $bundle                    Bundle
     * @param string|null                      $parentSubscription        Parent subscription
     * @param Site|null                        $site                      Site
     */
    public function __construct(
        array $purchaseDetails,
        EpochCCRetrieveTransactionResult $retrieveTransactionResult,
        Bundle $bundle,
        ?string $parentSubscription,
        ?Site $site = null
    ) {
        $tax            = $purchaseDetails['amounts'] ?? $purchaseDetails['tax'] ?? null;
        $bundleId       = $purchaseDetails['bundle_id'] ?? $purchaseDetails['bundleId'] ?? null;
        $addOnId        = $purchaseDetails['add_on_id'] ?? $purchaseDetails['addonId'] ?? null;
        $itemId         = $purchaseDetails['item_id'] ?? $purchaseDetails['itemId'] ?? null;
        $rebillStart    = $purchaseDetails['initial_days'] ?? $purchaseDetails['initialDays'] ?? null;
        $subscriptionId = $purchaseDetails['subscription_id'] ?? $purchaseDetails['subscriptionId'] ?? null;
        $isTrial        = $purchaseDetails['is_trial'] ?? $purchaseDetails['isTrial'] ?? null;

        if ($tax === null) {
            $tax = $this->createTaxPayloadFromAmounts(
                $retrieveTransactionResult->transactionInformation()->amount(),
                $retrieveTransactionResult->transactionInformation()->rebillAmount(),
            );
        }

        if ($rebillStart === null && $retrieveTransactionResult->transactionInformation()->rebillStart() !== null) {
            $rebillStart = $retrieveTransactionResult->transactionInformation()->rebillStart();
        }

        parent::__construct(
            $bundleId,
            $addOnId,
            $tax,
            $retrieveTransactionResult->siteId(),
            $retrieveTransactionResult->transactionInformation()->transactionId(),
            $retrieveTransactionResult->transactId() ?? null,
            $itemId,
            $retrieveTransactionResult->transactionInformation()->amount(),
            $retrieveTransactionResult->transactionInformation()->createdAt(),
            $retrieveTransactionResult->transactionInformation()->rebillAmount(),
            $retrieveTransactionResult->transactionInformation()->rebillFrequency(),
            is_null($rebillStart) ?: (int) $rebillStart,
            $subscriptionId,
            $parentSubscription,
            $isTrial,
            false, // Will be received in purchaseDetails once implemented
            false, // Will be received in retrieveTransactionDetails once implemented
            false, // Will need to be updated once implemented in business logic
            false, // Will need to be updated once implemented in business logic
            false, // Will need to be updated once implemented in business logic
            false, // Will need to be updated once implemented in business logic
            false,
            $bundle->isRequireActiveContent(),
            $purchaseDetails['state']
        );

        $this->billerTransactions = $retrieveTransactionResult->billerTransactions()->toArray();
    }
}