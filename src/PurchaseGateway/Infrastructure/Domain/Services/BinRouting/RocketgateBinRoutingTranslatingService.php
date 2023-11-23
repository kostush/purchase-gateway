<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\BinRoutingCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\ItemId;
use ProBillerNG\PurchaseGateway\Domain\Model\NewCCPaymentInfo;
use ProbillerNG\PurchaseGateway\Domain\Model\ChequePaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Services\BinRoutingService;

class RocketgateBinRoutingTranslatingService implements BinRoutingService
{
    /**
     * @var RocketgateBinRoutingAdapter adapter
     */
    protected $adapter;

    /**
     * RocketgateBinRoutingTranslatingService constructor.
     * @param BinRoutingServiceAdapter $adapter adapter
     */
    public function __construct(BinRoutingServiceAdapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @param PurchaseProcess $purchaseProcess The purchase process manager
     * @param ItemId          $itemId          The item id
     * @param Site            $site            The site id
     * @param BillerMapping   $billerMapping   Biller Mapping
     * @return BinRoutingCollection|null
     * @throws Exceptions\BinRoutingCodeApiException
     * @throws Exceptions\BinRoutingCodeErrorException
     * @throws Exceptions\BinRoutingCodeTypeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function retrieveRoutingCodes(
        PurchaseProcess $purchaseProcess,
        ItemId $itemId,
        Site $site,
        BillerMapping $billerMapping
    ): ?BinRoutingCollection {

        if (!$site->isBinRoutingServiceEnabled()) {
            return new BinRoutingCollection();
        }
        return $this->getRocketgateBinCollections($purchaseProcess, $itemId, $billerMapping);
    }

    /**
     * @param PurchaseProcess $purchaseProcess The purchase process manager
     * @param ItemId          $itemId          The Item Id
     * @param BillerMapping   $billerMapping   Biller Mapping
     * @return BinRoutingCollection
     * @throws Exceptions\BinRoutingCodeApiException
     * @throws Exceptions\BinRoutingCodeErrorException
     * @throws Exceptions\BinRoutingCodeTypeException
     * @throws Exception
     */
    private function getRocketgateBinCollections(
        PurchaseProcess $purchaseProcess,
        ItemId $itemId,
        BillerMapping $billerMapping
    ): BinRoutingCollection {

        if (!empty($purchaseProcess->paymentTemplateCollection())
            && $purchaseProcess->retrieveSelectedPaymentTemplate() !== null) {
            // Get bin from selected payment template for sec rev
            $bin = $purchaseProcess->retrieveSelectedPaymentTemplate() ? (
            $purchaseProcess->retrieveSelectedPaymentTemplate()->firstSix()
            ) : "";
        } else {
            //$bin = $purchaseProcess->fraudAdvice()->bin();
            // TODO - This should not have to depend on the purchase process this deep on the flow
            $paymentInfo = $purchaseProcess->paymentInfo();
            if ($paymentInfo instanceof NewCCPaymentInfo) {
                $bin = substr($paymentInfo->ccNumber(), 0, 6);
            } else {
                $bin = '';
            }
        }

        /** @var RocketgateBinRoutingAdapter $adapter */
        return $this->adapter->retrieve(
            (string) $bin,
            $billerMapping->billerFields()->merchantId(),
            (string) $purchaseProcess->currency()->getValue(),
            $purchaseProcess->cascade()->currentBillerSubmit() ?: 1,
            (string) $itemId,
            (string) $billerMapping->businessGroupId()
        );
    }
}
