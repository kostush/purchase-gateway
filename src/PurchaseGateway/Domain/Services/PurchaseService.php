<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use Exception;
use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\Exceptions\CannotCreateIntegrationEventException;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\BaseEvent;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseDomainEventFactory;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\PurchaseEntityCannotBeCreatedException;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\MemberId;
use ProBillerNG\PurchaseGateway\Domain\Model\ProcessedBundleItem;
use ProBillerNG\PurchaseGateway\Domain\Model\ProcessedItemsCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Purchase;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseRepository;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\SubscriptionInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;

class PurchaseService
{
    /**
     * @var PurchaseRepository
     */
    protected $purchaseRepository;

    /**
     * @var PurchaseProcess
     */
    protected $purchaseProcess;

    /**
     * @var Purchase
     */
    protected $purchase;

    /**
     * @var LegacyImportService
     */
    protected $legacyImportService;

    /**
     * @var CreatePaymentTemplateService
     */
    private $paymentTemplateService;

    /**
     * PurchaseService constructor.
     *
     * @param PurchaseRepository           $purchaseRepository     Purchase Repository
     * @param LegacyImportService          $legacyImportService    LegacyImportService
     * @param CreatePaymentTemplateService $paymentTemplateService CreatePaymentTemplateService
     */
    public function __construct(
        PurchaseRepository $purchaseRepository,
        LegacyImportService $legacyImportService,
        CreatePaymentTemplateService $paymentTemplateService
    ) {
        $this->purchaseRepository     = $purchaseRepository;
        $this->legacyImportService    = $legacyImportService;
        $this->paymentTemplateService = $paymentTemplateService;
    }

    /**
     * @param PurchaseProcess $purchaseProcess Purchase Process
     * @param Site            $site            Site
     *
     * @return Purchase|null
     * @throws LoggerException
     * @throws Exception
     * @throws PurchaseEntityCannotBeCreatedException
     */
    public function createPurchaseEntity(PurchaseProcess $purchaseProcess, Site $site): ?Purchase
    {
        $this->purchaseProcess = $purchaseProcess;

        if (!$this->purchaseProcess->isProcessed()) {
            Log::info("CreatePurchaseEntity is not processed.");
            return null;
        }

        if (!$this->purchaseProcess->wasMainItemPurchaseSuccessful()
            && !$site->isNsfSupported()
        ) {
            Log::info("CreatePurchaseEntity Main item was not successful and site doesn't support NSF.");
            return null;
        }

        if (!$this->purchaseProcess->wasMainItemPurchaseSuccessful()
            && !$this->purchaseProcess->retrieveMainPurchaseItem()->wasItemNsfPurchase()
        ) {
            Log::info("CreatePurchaseEntity Main item was not successful and main item was not NSF purchase.");
            return null;
        }

        if ($this->purchaseProcess->retrieveMainPurchaseItem()->wasItemNsfPurchase()
            && !$this->isNewCCPurchase($purchaseProcess->retrieveMainPurchaseItem())
        ) {
            Log::info("CreatePurchaseEntity Main item was NSF but with a payment template (not New CC)");
            return null;
        }

        try {
            Log::info("CreatePurchaseEntity Attempt to create Payment Template.");

            if ($this->isNewCCPurchase($purchaseProcess->retrieveMainPurchaseItem())) {
                Log::info('PaymentTemplateCreation Creating Payment template: purchase with new credit card.');

                $this->paymentTemplateService->addPaymentTemplate(
                    $this->purchaseProcess->memberId(),
                    $this->purchaseProcess->retrieveMainPurchaseItem()->lastTransactionId(),
                    $this->shouldCreateForNsf($site)
                );
            } else {
                Log::info('PaymentTemplateCreation Not create payment template: purchase with existing credit card.');
            }
        } catch (\Exception $e) {
            Log::info(
                "PaymentTemplateCreation Could not create payment template with sync. Attempting async.",
                [
                    "sessionId"     => (string) $this->purchaseProcess->sessionId(),
                    "transactionId" => (string) $this->purchaseProcess->retrieveMainPurchaseItem()->lastTransactionId(),
                    "message"       => $e->getMessage(),
                    "code"          => $e->getCode(),
                ]
            );

            $this->paymentTemplateService->createPaymentTemplateAsyncEvent(
                $this->purchaseProcess->retrieveMainPurchaseItem()->lastTransactionId(),
                $this->purchaseProcess->purchaseId(),
                $this->purchaseProcess->memberId()
            );
        }

        try {
            $processedItemsCollection = new ProcessedItemsCollection();
            $memberId                 = $this->purchaseProcess->buildMemberId();

            /** @var InitializedItem $item */
            foreach ($this->purchaseProcess->initializedItemCollection() as $itemId => $item) {
                $addonCollection = new AddonCollection();
                $addonCollection->offsetSet((string) $item->itemId(), $item->addonId());
                $processedItemsCollection->offsetSet(
                    (string) $item->itemId(),
                    ProcessedBundleItem::create(
                        SubscriptionInfo::create(
                            $item->buildSubscriptionId(),
                            (string) $this->purchaseProcess->userInfo()->username()
                        ),
                        $item->itemId(),
                        $item->transactionCollection(),
                        $item->bundleId(),
                        $addonCollection,
                        $item->isCrossSale()
                    )
                );
            }

            $this->purchase = Purchase::create(
                $this->purchaseProcess->buildPurchaseId(),
                $memberId,
                $this->purchaseProcess->sessionId(),
                $processedItemsCollection,
                $this->purchaseProcess->retrieveMainPurchaseItem()->lastTransactionState()
            );
        } catch (Exception $e) {
            throw new PurchaseEntityCannotBeCreatedException();
        }

        $this->handlePurchaseProcessedDomainEvent();

        $this->purchaseRepository->add($this->purchase);

        $this->purchaseProcess->setPurchase($this->purchase);

        Log::info('Purchase entity created', ['purchaseId' => $this->purchase->getEntityId()]);

        return $this->purchase;
    }

    /**
     * @param InitializedItem $item
     * @return bool|null
     * @throws LoggerException
     */
    private function isNewCCPurchase(InitializedItem $item): bool
    {
        if (!$item->lastTransaction() instanceof Transaction) {
            log::alert('PaymentTemplateCreation we should have a transaction at this point, no transaction found.');
            return false;
        }

        if (empty($item->lastTransaction()->newCCUsed())) {
            log::info('PaymentTemplateCreation newCCUsed is empty.');
            return false;
        }

        return $item->lastTransaction()->newCCUsed();
    }

    /**
     * @param Site $site Site
     * @return bool
     * @throws LoggerException
     */
    protected function shouldCreateForNsf(Site $site)
    {
        log::info(
            'PaymentTemplateCreation Should Create for nsf',
            [
                'siteIsNsfSupported' => $site->isNsfSupported(),
                'isForceThreeD'      => ($this->purchaseProcess->fraudAdvice() ? $this->purchaseProcess->fraudAdvice()->isForceThreeD() : false),
                'wasItemNsfPurchase' => $this->purchaseProcess->retrieveMainPurchaseItem()->wasItemNsfPurchase()
            ]
        );

        return (
            $site->isNsfSupported()                                                                                       // NSF supported
            && $this->purchaseProcess->retrieveMainPurchaseItem()->wasItemNsfPurchase()                                   // Was NSF transaction
        );
    }

    /**
     * @param PurchaseProcess $purchaseProcess Purchase Process
     * @param bool            $forRebill       For rebill we need to create purchase entity for events
     *
     * @return Purchase|null
     * @throws PurchaseEntityCannotBeCreatedException
     * @throws LoggerException|CannotCreateIntegrationEventException
     */
    public function createPurchaseEntityForThirdParty(PurchaseProcess $purchaseProcess, bool $forRebill = false): ?Purchase
    {
        $this->purchaseProcess = $purchaseProcess;

        if (!$this->purchaseProcess->wasMainItemPurchaseSuccessfulOrPending() && !$forRebill) {
            return null;
        }

        try {
            $processedItemsCollection = new ProcessedItemsCollection();

            $memberId = $this->purchaseProcess->buildMemberId();

            $existingPurchase = $this->purchaseRepository->findById($this->purchaseProcess->buildPurchaseId());

            /** @var InitializedItem $item */
            foreach ($this->purchaseProcess->initializedItemCollection() as $itemId => $item) {
                if ($item->isCrossSale() && $item->lastTransactionState() === Transaction::STATUS_APPROVED) {
                    $item->markCrossSaleAsSelected();
                }

                if (null === $existingPurchase) {
                    $addonCollection = new AddonCollection();
                    $addonCollection->offsetSet((string) $item->itemId(), $item->addonId());

                    $processedItemsCollection->offsetSet(
                        (string) $item->itemId(),
                        ProcessedBundleItem::create(
                            SubscriptionInfo::create(
                                $item->buildSubscriptionId(),
                                (string) $this->purchaseProcess->userInfo()->username()
                            ),
                            $item->itemId(),
                            $item->transactionCollection(),
                            $item->bundleId(),
                            $addonCollection,
                            $item->isCrossSale()
                        )
                    );

                    continue;
                }

                /** @var ProcessedBundleItem $retrievedItem */
                foreach ($existingPurchase->items() as $index => $retrievedItem) {
                    if ((string) $retrievedItem->itemId() === (string) $item->itemId()) {
                        $retrievedItem->updateTransactionCollection($item->transactionCollection());
                        $retrievedItem->setIsCrossSale($item->isCrossSale());
                        $existingPurchase->items()->offsetSet(
                            (string) $item->itemId(),
                            $retrievedItem
                        );
                        $existingPurchase->items()->offsetUnset($index);
                    }
                }
            }

            $this->initPurchaseEntity($existingPurchase, $memberId, $processedItemsCollection);
        } catch (Exception $e) {
            throw new PurchaseEntityCannotBeCreatedException();
        }

        $this->handlePurchaseProcessedDomainEvent();

        // for rebill we need to skip saving the purchase because of item ids needed on member profile
        // if we generate new item ids, purchased bundles won't be updated because they cannot be found
        if (!$forRebill) {
            $this->purchaseRepository->addOrUpdatePurchase($this->purchase);
        }

        $this->purchaseProcess->setPurchase($this->purchase);

        Log::info('Purchase entity created', ['purchaseId' => $this->purchase->getEntityId()]);

        return $this->purchase;
    }

    /**
     * @param PurchaseProcess $purchaseProcess Purchase process
     * @return void
     * @throws Exception
     */
    public function restorePurchaseInSession(PurchaseProcess $purchaseProcess): void
    {
        $existingPurchase = $this->purchaseRepository->findById($purchaseProcess->buildPurchaseId());

        if (!is_null($existingPurchase)) {

            /** @var ProcessedBundleItem $retrievedItem */
            foreach ($existingPurchase->items() as $index => $retrievedItem) {
                $existingPurchase->items()->offsetSet(
                    (string) $retrievedItem->itemId(),
                    $retrievedItem
                );
                $existingPurchase->items()->offsetUnset($index);
            }
            $purchaseProcess->setPurchase($existingPurchase);
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function createPurchaseDomainEvent(): void
    {
        $this->purchase->sendProcessedEvent(
            PurchaseDomainEventFactory::create($this->purchaseProcess, $this->purchase)
        );
    }

    /**
     * @return BaseEvent
     * @throws Exception
     */
    protected function createPurchaseProcessedDomainEvent(): BaseEvent
    {
        return PurchaseDomainEventFactory::create($this->purchaseProcess, $this->purchase);
    }

    /**
     * @param Purchase|null            $existingPurchase         Existing purchase
     * @param MemberId                 $memberId                 Member id
     * @param ProcessedItemsCollection $processedItemsCollection Processed items collection
     * @return void
     * @throws Exception
     */
    protected function initPurchaseEntity(
        ?Purchase $existingPurchase,
        MemberId $memberId,
        ProcessedItemsCollection $processedItemsCollection
    ): void {
        if (null === $existingPurchase) {
            $this->purchase = Purchase::create(
                $this->purchaseProcess->buildPurchaseId(),
                $memberId,
                $this->purchaseProcess->sessionId(),
                $processedItemsCollection,
                $this->purchaseProcess->retrieveMainPurchaseItem()->lastTransactionState()
            );

            return;
        }

        $this->purchase = $existingPurchase;
    }

    /**
     * @throws LoggerException
     * @throws Exception
     */
    private function handlePurchaseProcessedDomainEvent(): void
    {
        /**
         * Here we are using flag for legacy import flag to turn on/off for legacy import functionality
         */
        if (!config('app.feature.legacy_api_import')) {
            $this->createPurchaseDomainEvent();
        } else {
            $domainEvent = $this->createPurchaseProcessedDomainEvent();
            Log::info("PurchaseService initial purchase processed domain event:", $domainEvent->toArray());

            $checkedDomainResponseEvent = $this->legacyImportService->handlerLegacyImportByApiEndPoint($domainEvent);
            Log::info("PurchaseService final purchase processed domain event:", $checkedDomainResponseEvent->toArray());

            $this->purchase->sendProcessedEvent($checkedDomainResponseEvent);

            if ($checkedDomainResponseEvent->isUsernamePadded()) {
                $this->purchaseProcess->usernamePadded();
            }
        }
    }
}
