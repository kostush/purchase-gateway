<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting;

use ProBillerNG\PurchaseGateway\Domain\Model\BillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\BinRoutingCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\ItemId;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\NewCCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Model\BinRouting;
use ProBillerNG\PurchaseGateway\Domain\Services\BinRoutingService;

class NetbillingBinRoutingTranslatingService implements BinRoutingService
{
    /**
     * @var NetbillingBinRoutingAdapter adapter
     */
    protected $adapter;

    /**
     * NetbillingBinRoutingTranslatingService constructor.
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

        // For Netbilling secondary purchase, bin routing code is retrieved from payment template
        if (!empty($purchaseProcess->paymentTemplateCollection())
            && $purchaseProcess->retrieveSelectedPaymentTemplate() !== null
            && isset($purchaseProcess->retrieveSelectedPaymentTemplate()->billerFields()['binRouting'])
        ) {
            $binRoutingCollection = new BinRoutingCollection();
            $binRoutingCollection->add(
                BinRouting::create(
                    $purchaseProcess->cascade()->currentBillerSubmit() ?: 1,
                    $purchaseProcess->retrieveSelectedPaymentTemplate()->billerFields()['binRouting'],
                    null
                )
            );
            return $binRoutingCollection;
        }

        return $this->getNetbillingBinCollections($purchaseProcess, $itemId, $billerMapping);
    }

    /**
     * @param PurchaseProcess $purchaseProcess The purchase process manager
     * @param ItemId          $itemId          The Item Id
     * @param BillerMapping   $billerMapping   Biller mapping
     * @return BinRoutingCollection
     * @throws Exceptions\BinRoutingCodeApiException
     * @throws Exceptions\BinRoutingCodeErrorException
     * @throws Exceptions\BinRoutingCodeTypeException
     * @throws \ProBillerNG\Logger\Exception
     */
    private function getNetbillingBinCollections(
        PurchaseProcess $purchaseProcess,
        ItemId $itemId,
        BillerMapping $billerMapping
    ): BinRoutingCollection {

        $joinSubmitNumber = $purchaseProcess->cascade()->currentBillerSubmit();

        $bin         = '';
        $paymentInfo = $purchaseProcess->paymentInfo();
        if ($paymentInfo instanceof NewCCPaymentInfo) {
            $bin = substr($paymentInfo->ccNumber(), 0, 6);
        }

        /** @var BillerMapping $billerMapping */
        return $this->adapter->retrieve(
            (string) $bin,
            $billerMapping->billerFields()->accountId(),
            $billerMapping->billerFields()->siteTag(),
            $joinSubmitNumber,
            (string) $itemId,
            (string) $billerMapping->businessGroupId()
        );
    }
}
