<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process;

use Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\ExistingPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\UnknownBiller;

class ProcessPurchaseGeneralHttpDTO extends ProcessPurchaseHttpDTO
{
    /**
     * @return void
     * @throws Exception
     */
    protected function responseData(): void
    {
        $this->response['sessionId']  = (string) $this->purchaseProcess->sessionId();
        $this->response['success']    = false;
        $this->response['purchaseId'] = (string) $this->purchaseProcess->purchaseId();

        /**
         * We are returning the memberId because in unsuccessful secondary revenue purchase
         * it needs to return the existent member, not create one.
         */
        $this->response['memberId'] = $this->purchaseProcess->memberId();

        $mainPurchase                 = $this->purchaseProcess->retrieveMainPurchaseItem();
        $purchase                     = $this->purchaseProcess->purchase();
        $this->response['nextAction'] = $this->buildNextAction();

        // expose biller name in the purchase response and postback
        $billerName = $mainPurchase->billerName();
        if (!empty($billerName) && $billerName != UnknownBiller::BILLER_NAME) {
            $this->response['billerName'] = $billerName;
        }

        if (is_null($mainPurchase->lastTransactionState())
            && $this->purchaseProcess->isCurrentBillerAvailablePaymentsMethods()
        ) {
            $this->response['success'] = true;
        }

        if (!$mainPurchase->wasItemPurchaseSuccessfulOrPending()) {
            // when we have a purchase with 3ds we cannot do an auth transaction
            // since we don't have card information
            if ($this->site()->isNsfSupported() && $mainPurchase->wasItemNsfPurchase()) {
                Log::info('ProcessPurchaseDTO Site supports NSF and main item purchase was NSF.');

                //Before setting it we need to make sure it's not secRev with payment template
                if (!($this->purchaseProcess->paymentInfo() instanceof ExistingPaymentInfo)) {
                    $this->response['isNsf'] = $mainPurchase->wasItemNsfPurchase();
                }

                if ($mainPurchase->wasItemNsfPurchase()) {
                    Log::info('ProcessPurchaseDTO Main item was NSF and subscription id is: ' . (string) $mainPurchase->subscriptionId());
                    $this->response['subscriptionId'] = (string) $mainPurchase->subscriptionId();
                }
            }

            // Not all billers have errorClassification
            if (!empty($mainPurchase->errorClassification())) {
                $this->response['errorClassification'] = $mainPurchase->errorClassification()->toArray();
            }

            return;
        }

        // BG-41472: Add last transaction in the final response
        // If there are multiple transactions from retries, only the last one will be sent. (check ticket for details)
        $lastTransactionId = (string) $mainPurchase->lastTransactionId();
        if (!empty($lastTransactionId)) {
            $this->response['transactionId'] = $lastTransactionId;
        }

        $this->response['success']  = true;
        $this->response['bundleId'] = (string) $mainPurchase->bundleId();
        $this->response['addonId']  = (string) $mainPurchase->addonId();
        $this->response['itemId']   = (string) $mainPurchase->itemId();

        if ($mainPurchase->wasItemPurchaseSuccessful()) {
            $this->response['subscriptionId'] = $mainPurchase->subscriptionId();
            if (config('app.feature.legacy_api_import')) {
                $this->response['isUsernamePadded'] = $this->purchaseProcess->isUsernamePadded();
            }

            if ($purchase !== null) {
                $processedBundle                  = $purchase->items()->offsetGet((string) $mainPurchase->itemId());
                $this->response['purchaseId']     = (string) $purchase->purchaseId();
                $this->response['subscriptionId'] = (string) $processedBundle->subscriptionInfo()->subscriptionId();
            }
        }

        $crossSales = $this->purchaseProcess->retrieveProcessedCrossSales();

        /** @var InitializedItem $crossSale */
        foreach ($crossSales as $crossSale) {
            if ($crossSale->lastTransaction() !== null && $crossSale->lastTransaction()->isPending()) {
                continue;
            }

            $crossSaleResponse = [
                'success'  => false,
                'bundleId' => (string) $crossSale->bundleId(),
                'addonId'  => (string) $crossSale->addonId(),
                'itemId'   => (string) $crossSale->itemId()
            ];

            if ($crossSale->wasItemPurchaseSuccessful()) {
                $crossSaleResponse['success']        = true;
                $crossSaleResponse['subscriptionId'] = $crossSale->subscriptionId();

                if ($purchase !== null) {
                    $processedBundle                     = $purchase->items()->offsetGet((string) $crossSale->itemId());
                    $crossSaleResponse['subscriptionId'] = (string) $processedBundle->subscriptionInfo()
                        ->subscriptionId();
                }
            }

            // Not all billers have errorClassification
            if (!empty($crossSale->errorClassification())) {
                $crossSaleResponse['errorClassification'] = $crossSale->errorClassification()->toArray();
            }

            if (!$crossSale->wasItemPurchaseSuccessfulOrPending()
                && $this->site()->isNsfSupported()) {

                //Before setting it we need to make sure it's not secRev with payment template
                if (!($this->purchaseProcess->paymentInfo() instanceof ExistingPaymentInfo)) {
                    $crossSaleResponse['isNsf'] = $crossSale->wasItemNsfPurchase();
                }

                if ($crossSale->wasItemNsfPurchase()) {
                    Log::info(
                        'ProcessPurchaseDTO Cross sale item was NSF and subscription id is: ' .
                        (string) $crossSale->subscriptionId()
                    );
                    $crossSaleResponse['subscriptionId'] = (string) $crossSale->subscriptionId();
                }
            }

            // BG-41472: Add last transaction in the final response
            // If there are multiple transactions from retries, only the last one will be sent.
            // (check ticket for details)
            $lastCSTransactionId = (string) $crossSale->lastTransactionId();
            if (!empty($lastCSTransactionId)) {
                $crossSaleResponse['transactionId'] = $lastCSTransactionId;
            }

            if ($mainPurchase->wasItemPurchasePending()) {
                $crossSaleResponse['success'] = true;
                unset($crossSaleResponse['subscriptionId']);
            }

            $this->response['crossSells'][] = $crossSaleResponse;
        }
    }
}
