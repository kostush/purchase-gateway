<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services\Mgpg;

use ProbillerMGPG\SubsequentOperations\Init\Item;
use ProbillerMGPG\SubsequentOperations\Init\Item\OneChargeItem;
use ProbillerMGPG\SubsequentOperations\Init\Item\OneChargeItemWithTax;
use ProbillerMGPG\SubsequentOperations\Init\Item\Price\PriceInfo;
use ProbillerMGPG\SubsequentOperations\Init\Item\Price\PriceInfoWithTax;
use ProbillerMGPG\SubsequentOperations\Init\Item\Price\RebillInfo;
use ProbillerMGPG\SubsequentOperations\Init\Item\Price\RebillInfoWithTax;
use ProbillerMGPG\SubsequentOperations\Init\Item\Price\TaxInfo;
use ProbillerMGPG\SubsequentOperations\Init\Item\RecurringChargeItem;
use ProbillerMGPG\SubsequentOperations\Init\Item\RecurringChargeItemWithTax;
use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\Services\Mgpg\NgResponseService;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\RebillFieldRequiredException;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\Mgpg\InitRebillUpdateRequest;
use Ramsey\Uuid\Uuid;

class RebillUpdateChargesService
{
    const TAX_PRODUCT_CLASSIFICATION_UNKNOWN = 'unknown';

    /* On MGPG, different from PGW, items can be bought in a bigger quantity */
    const ITEM_QUANTITY = 1;

    /**
     * Rebill update is no multi-charge.
     */
    const IS_MAIN_CHARGE = TRUE;

    /**
     * @var RetrieveTransactionIdService
     */
    private $retrieveTransactionIdService;

    /**
     * @var InitRebillUpdateRequest
     */
    private $initRequest;

    /**
     * InitPurchaseNgToMgpgService constructor.
     * @param RetrieveTransactionIdService $retrieveTransactionIdService
     */
    public function __construct(
        RetrieveTransactionIdService $retrieveTransactionIdService
    ) {
        $this->retrieveTransactionIdService = $retrieveTransactionIdService;
    }

    /**
     * @param InitRebillUpdateRequest $initRequest
     * @return array
     * @throws Exception
     * @throws RebillFieldRequiredException
     * @throws \Exception
     */
    public function create(InitRebillUpdateRequest $initRequest): array
    {
        $charges           = [];
        $this->initRequest = $initRequest;

        $subsequentOperationClass = SubsequentOperationFactory::createSubsequentOperation(
            $initRequest->getBusinessTransactionOperation()
        );

        $charge = $this->createRebillUpdateChargeItem();
        Log::info('CreatingRebillUpdateCharges Charge created', ['charge' => $charge->toArray()]);

        $operation = new $subsequentOperationClass(
            $this->createChargeId(),
            $this->retrieveTransactionIdService->findByItemIdOrReturnItemId($initRequest->getItemId()),
            (string) $initRequest->getSite()->siteId(),
            $initRequest->getSite()->name(),
            [$charge],
            self::IS_MAIN_CHARGE
        );

        array_push(
            $charges,
            $operation
        );

        return $charges;
    }

    /**
     * @return OneChargeItem|OneChargeItemWithTax|RecurringChargeItem|RecurringChargeItemWithTax
     * @throws Exception
     * @throws RebillFieldRequiredException
     */
    private function createRebillUpdateChargeItem()
    {
        if (!$this->requestHasRebillInformation()) {
            throw new RebillFIeldRequiredException();
        }

        if ($this->requestHasTaxes()) {
            Log::info('CreatingRebillUpdateCharge Creating recurring charge item with tax');
            return new RecurringChargeItemWithTax(
                Item::REVENUE_STREAM_SECONDARY_SALE,
                $this->initRequest->getBundleId(),
                $this->initRequest->getSite()->name(),
                $this->initRequest->getSite()->name(),
                self::ITEM_QUANTITY,
                $this->createPriceWithTax(),
                $this->createRebillWithTax(),
                $this->createTax(),
                $this->createEntitlements(),
                $this->createLegacyMapping()
            );
        }

        Log::info('CreatingRebillUpdateCharge Creating recurring charge item without tax');
        return new RecurringChargeItem(
            Item::REVENUE_STREAM_SECONDARY_SALE,
            $this->initRequest->getBundleId(),
            $this->initRequest->getSite()->name(),
            $this->initRequest->getSite()->name(),
            self::ITEM_QUANTITY,
            $this->createPriceWithOutTax(),
            $this->createRebill(),
            $this->createEntitlements(),
            $this->createLegacyMapping()
        );
    }

    /**
     * @return PriceInfo
     */
    private function createPriceWithOutTax():PriceInfo
    {
        return new PriceInfo(
            $this->initRequest->getAmount(),
            $this->initRequest->getInitialDays(),
            $this->initRequest->getAmount()
        );
    }

    /**
     * @return PriceInfoWithTax
     */
    private function createPriceWithTax(): PriceInfoWithTax
    {
        return new PriceInfoWithTax(
            $this->initRequest->getInitialAmountBeforeTaxes(),
            $this->initRequest->getInitialDays(),
            $this->initRequest->getInitialAmountTaxes(),
            $this->initRequest->getInitialAmountAfterTaxes()
        );
    }

    /**
     * @return RebillInfoWithTax
     */
    private function createRebillWithTax(): RebillInfoWithTax
    {
        return new RebillInfoWithTax(
            $this->initRequest->getRebillAmountBeforeTaxes(),
            $this->initRequest->getRebillDays() ?? 0,
            $this->initRequest->getRebillAmountTaxes(),
            $this->initRequest->getRebillAmountAfterTaxes(),
            $this->initRequest->getAddRemainingDays()
        );
    }

    /**
     * @return RebillInfo
     */
    private function createRebill(): RebillInfo
    {
        return new RebillInfo(
            $this->initRequest->getRebillAmount(),
            $this->initRequest->getRebillDays() ?? 0,
            $this->initRequest->getRebillAmount(),
            $this->initRequest->getAddRemainingDays()
        );
    }

    /**
     * @return TaxInfo
     */
    private function createTax(): TaxInfo
    {
        return new TaxInfo(
            $this->initRequest->getTaxApplicationId() ?? '',
            self::TAX_PRODUCT_CLASSIFICATION_UNKNOWN,
            $this->initRequest->getTaxName() ?? '',
            $this->initRequest->getTaxRate() ?? 0.0,
            $this->initRequest->getTaxType() ?? '',
            $this->displayChargedAmount()
        );
    }

    /**
     * @return array
     */
    private function createEntitlements(): array
    {
        $charge = $this->initRequest->toArray();

        $entitlements = [
            'siteId'   => $charge['siteId'],
            'bundleId' => $charge['bundleId'],
            'addonId'  => $charge['addonId'],
        ];

        return [
            [
                'memberProfile' => [
                    'data' => array_filter($entitlements)
                ]
            ]
        ];
    }

    /**
     * For the moment, we use entitlements to pass the addonId and retrieve it back on process response.
     * @return array
     */
    public function createLegacyMapping(): ?array
    {
        $charge = $this->initRequest->toArray();

        $mapping = null;

        $legacyMappingProperties = NgResponseService::getLegacyMappingProperties();

        foreach ($legacyMappingProperties as $property => $typesOfProperty) {
            if(isset($charge['legacyMapping'][$property])) {
                $value = $charge['legacyMapping'][$property];
                settype($value, $typesOfProperty);
                $mapping[$property] = $value;
            }
        }

        if (empty($mapping)) {
            return null;
        }

        return $mapping;
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function createChargeId(): string
    {
        return Uuid::uuid4()->toString();
    }

    /**
     * @return bool
     */
    private function requestHasRebillInformation(): bool
    {
        return $this->initRequest->getRebillDays() !== null &&
            $this->initRequest->getRebillAmount() !== null;
    }

    /**
     * @return bool
     */
    private function requestHasTaxes(): bool
    {
        return !empty($this->initRequest->getTax()) &&
            !empty($this->initRequest->getTaxInitialAmount()) &&
            !empty($this->initRequest->getTaxRebillAmount());
    }

    /**
     * @return bool
     */
    private function displayChargedAmount(): bool
    {
        if($this->initRequest->getTaxType() !==null && strtolower($this->initRequest->getTaxType()) == 'vat') {
            return true;
        }
        return false;
    }
}
