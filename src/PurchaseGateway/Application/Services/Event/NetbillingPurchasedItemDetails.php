<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\Event;

use ProBillerNG\PurchaseGateway\Domain\Model\Bundle;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\NetbillingCCRetrieveTransactionResult;

class NetbillingPurchasedItemDetails extends PurchasedItemDetails
{
    /**
     * @var string
     */
    private $siteTag;

    /**
     * @var string|null
     */
    private $billerMemberId;

    /**
     * @var string
     */
    private $controlKeyword;

    /**
     * @var array array
     */
    protected $billerTransactions = [];

    /**
     * RocketgatePurchasedItemDetails constructor.
     * @param array                                 $purchaseDetails           Purchase details
     * @param NetbillingCCRetrieveTransactionResult $retrieveTransactionResult Retrieve result
     * @param Bundle                                $bundle                    Bundle
     * @param string|null                           $parentSubscription        Parent subscription
     * @param Site|null                             $site Site
     */
    public function __construct(
        array $purchaseDetails,
        NetbillingCCRetrieveTransactionResult $retrieveTransactionResult,
        Bundle $bundle,
        ?string $parentSubscription,
        ?Site $site = null
    ) {
        $isNsfSupported = false;

        if ($site) {
            $isNsfSupported = $site->isNsfSupported();
        }
        $tax            = $purchaseDetails['amounts'] ?? $purchaseDetails['tax'] ?? null;
        $bundleId       = $purchaseDetails['bundle_id'] ?? $purchaseDetails['bundleId'] ?? null;
        $addOnId        = $purchaseDetails['add_on_id'] ?? $purchaseDetails['addonId'] ?? null;
        $itemId         = $purchaseDetails['item_id'] ?? $purchaseDetails['itemId'] ?? null;
        $rebillStart    = $purchaseDetails['initial_days'] ?? $purchaseDetails['initialDays'] ?? null;
        $subscriptionId = $purchaseDetails['subscription_id'] ?? $purchaseDetails['subscriptionId'] ?? null;
        $isTrial        = $purchaseDetails['is_trial'] ?? $purchaseDetails['isTrial'] ?? null;

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
            $rebillStart,
            $subscriptionId,
            $parentSubscription,
            $isTrial,
            false, // Will be received in purchaseDetails once implemented
            $isNsfSupported && $retrieveTransactionResult->transactionInformation()->isNsf(),
            false, // Will need to be updated once implemented in business logic
            false, // Will need to be updated once implemented in business logic
            false, // Will need to be updated once implemented in business logic
            false, // Will need to be updated once implemented in business logic
            false,
            $bundle->isRequireActiveContent(),
            $purchaseDetails['state']
        );

        $this->siteTag            = $retrieveTransactionResult->billerFields()->siteTag();
        $this->billerMemberId     = $retrieveTransactionResult->billerMemberId();
        $this->controlKeyword     = $retrieveTransactionResult->billerFields()->merchantPassword();
        $this->billerTransactions = $retrieveTransactionResult->billerTransactions()->toArray();

        if ((!$isNsfSupported && $retrieveTransactionResult->transactionInformation()->isNsf())
            && !$retrieveTransactionResult->securedWithThreeD()
        ) {
            foreach ($this->billerTransactions as $key => $billerTransaction) {
                if ($billerTransaction->getType() != 'auth') {
                    continue;
                }

                unset($this->billerTransactions[$key]);
            }
        }
    }

    /**
     * @return string
     */
    public function siteTag()
    {
        return $this->siteTag;
    }

    /**
     * @return string|null
     */
    public function billerMemberId(): ?string
    {
        return $this->billerMemberId;
    }

    /**
     * @return string
     */
    public function controlKeyword(): string
    {
        return $this->controlKeyword;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return array_merge(get_object_vars($this), parent::toArray());
    }
}
