<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Mgpg;

use ProbillerMGPG\Common\PaymentMethod;
use ProbillerMGPG\Common\PaymentType;
use ProbillerMGPG\Purchase\Common\NextAction;
use ProbillerMGPG\Purchase\Process\PurchaseProcessResponse;
use ProbillerMGPG\Purchase\Process\Response\Charge;
use ProbillerMGPG\Purchase\Process\Response\Item;
use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\Mgpg\CompletedProcessPurchaseCommand;
use ProBillerNG\PurchaseGateway\Application\Services\Mgpg\MgpgResponseService;
use ProBillerNG\PurchaseGateway\Application\Services\Mgpg\ProcessPurchaseCommand;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonId;
use ProBillerNG\PurchaseGateway\Domain\Model\Amount;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleId;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleRebillChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleSingleChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\Duration;
use ProBillerNG\PurchaseGateway\Domain\Model\ErrorClassification;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidAmountException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidStateException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentMethodException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentTypeException;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\GenericPurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItemCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\ItemId;
use ProBillerNG\PurchaseGateway\Domain\Model\MemberId;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\NextAction as NGNextAction;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\NextActionProcessFactory;
use ProBillerNG\PurchaseGateway\Domain\Model\OtherPaymentTypeInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\ProcessedBundleItem;
use ProBillerNG\PurchaseGateway\Domain\Model\ProcessedItemsCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Purchase;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\AbstractState;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Pending;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processed;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\ThreeDLookupPerformed;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Valid;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Model\SubscriptionInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\TaxBreakdown;
use ProBillerNG\PurchaseGateway\Domain\Model\TaxInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\TaxType;
use ProBillerNG\PurchaseGateway\Domain\Model\ThirdParty;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Model\UnknownBiller;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\JsonWebToken;
use ProBillerNG\PurchaseGateway\Code;

/**
 * Class PurchaseProcess This MGPG version provides an alternate version that is only concerned with what
 * is needed to correctly assemble a DTO since MGPG handles the state tracking and other details. For the
 * needs of the MGPG process endpoint, all the heavy-lifting is done on MGPG.
 * @package ProBillerNG\PurchaseGateway\UI\Http\Controllers\Mgpg
 */
class PurchaseProcess extends GenericPurchaseProcess
{
    /**
     * @var NGNextAction
     */
    protected $nextAction;

    /**
     * @var MgpgResponseService
     */
    protected $mgpgResponseService;

    /**
     * @var TokenGenerator
     */
    protected $tokenGenerator;

    /**
     * @var CryptService
     */
    protected $cryptService;

    /**
     * @var array|Transaction[]
     */
    protected $transactions;

    /**
     * @var ProcessPurchaseCommand
     */
    protected $command;

    /**
     * @var string
     */
    protected $rawInvoice;

    /**
     * PurchaseProcess constructor.
     * @param PurchaseProcessResponse $response MGPG Response
     * @param ProcessPurchaseCommand  $command
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws InvalidStateException
     */
    public function __construct(
        PurchaseProcessResponse $response,
        ProcessPurchaseCommand $command
    ) {
        $this->sessionId      = SessionId::createFromString($command->getNgSessionId());
        $this->command        = $command;
        $this->publicKeyIndex = $command->getPublicKeyId();

        $this->mgpgResponseService = app()->make(MgpgResponseService::class);
        $this->tokenGenerator      = app()->make(TokenGenerator::class);
        $this->cryptService        = app()->make(CryptService::class);
        $this->transactions        = $this->createTransactions($response);
        $this->nextAction          = $this->createNextAction($response->nextAction);
        $this->fraudAdvice         = $this->createFraud($response->nextAction);

        // We frequently have no invoice from mgpg for flows that involve extra steps(3rd party, 3ds, fraud, etc).
        if ($response->invoice) {
            $this->purchaseId                = $response->invoice->invoiceId;
            $this->memberId                  = $response->invoice->memberId;
            $this->redirectUrl               = $response->invoice->redirectUrl;
            $this->postbackUrl               = $response->invoice->postbackUrl;
            $this->initializedItemCollection = $this->createInitializedItems($response->invoice->charges);
            $this->purchase                  = $this->createPurchase();
            $this->rawInvoice                = $response->invoice->rawBody;
            $this->paymentInfo               = $this->createPaymentInfo($response->invoice->paymentInfo);
        }
    }

    /**
     * @param \ProbillerMGPG\Purchase\Process\Response\PaymentInfo|null $paymentInfoResponse
     * @return PaymentInfo|null
     * @throws Exception
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     */
    protected function createPaymentInfo(?\ProbillerMGPG\Purchase\Process\Response\PaymentInfo $paymentInfoResponse): ?PaymentInfo
    {
        if (empty($paymentInfoResponse)){
            return null;
        }

        $paymentInfo = $paymentInfoResponse->toArray();

        if (!empty($paymentInfo["paymentMethod"]) && $paymentInfo["paymentMethod"] === PaymentMethod::GIFTCARDS) {
            $paymentInfoObject = OtherPaymentTypeInfo::build($paymentInfo["paymentType"], $paymentInfo["paymentMethod"]);
            return $paymentInfoObject;
        }
        return null;
    }

    /**
     * We must build our own main transaction since the adaptor is stateless(can't fetch from session).
     *
     * We fill it with whatever MGPG gives us as an accurate representation of what the
     * transaction state should be based on their response.
     *
     * We always create at least one transaction since MGPG will return `nextAction` and we need one to properly create
     * the NG nextAction. Since it doesn't always return an invoice like NG does, we may not create the other ones.
     * @param PurchaseProcessResponse $response
     * @return array Of Transactions
     * @throws \Exception
     */
    protected function createTransactions(PurchaseProcessResponse $response): array
    {
        $transactions = [];
        $chargeCnt    = 0;
        $isNsf = null;
        $errorClassification = null;

        // We must create at least one transaction as a placeholder for the PurchaseProcess DTO to work.
        do {
            $nextAction          = $response->nextAction;
            $deviceCollectionUrl = null;
            $deviceCollectionJwt = null;
            $transactionId       = TransactionId::create();
            $state               = Transaction::STATUS_PENDING;

            if ($response->invoice) {
                $transactionId = TransactionId::createFromString($response->invoice->charges[$chargeCnt]->transactionId);
                $charge = $response->invoice->charges[$chargeCnt];

                switch ($charge->status) {
                    case "success":
                    {
                        $state = Transaction::STATUS_APPROVED;
                        break;
                    }
                    case "decline":
                    {
                        $state = Transaction::STATUS_DECLINED;
                        $isNsf = false;
                        if (isset($charge->errorClassification)) {
                            $isNsf = $this->isNSF($charge->errorClassification['errorType']);
                            $errorClassification = new ErrorClassification(
                                $charge->errorClassification['groupDecline'],
                                $charge->errorClassification['errorType'],
                                $charge->errorClassification['groupMessage'],
                                $charge->errorClassification['recommendedAction']
                            );
                        }
                        break;
                    }
                }
            }

            $transactions[(string) $transactionId] = Transaction::create(
                $transactionId,
                $state,
                UnknownBiller::BILLER_NAME,
                null,
                null,
                $nextAction->threeD->paReq ?? null,
                null,                              //TODO when MGPG handles 3rd Party
                $isNsf,
                $nextAction->threeD->deviceCollectionUrl ?? null,
                $nextAction->threeD->deviceCollectionJWT ?? null,
                $errorClassification
            );

            $chargeCnt++;

        } while (isset($response->invoice->charges) && $chargeCnt < count($response->invoice->charges));

        return $transactions;
    }

    /**
     * @return bool
     */
    public function hasFailedTransactions(): bool
    {
        $hasFailures = false;
        foreach ($this->transactions as $transaction) {
           if ($transaction->state() != Transaction::STATUS_APPROVED) {
               $hasFailures = true;
               break;
           }
        }

        return $hasFailures;
    }

    /**
     * @return InitializedItem|null
     */
    public function getFirstSuccessfulItem() : ?InitializedItem
    {
        /** @var InitializedItem $item */
        foreach ($this->initializedItemCollection as $item){
            if ($item->wasItemPurchaseSuccessful()){
                return $item;
            }
        }
        return null;
    }

    /**
     * @param NextAction $nextAction MGPG NextAction
     * @return NGNextAction NG NextAction Process
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws InvalidStateException
     */
    protected function createNextAction(NextAction $nextAction): NGNextAction
    {
        $mainTransaction = $this->transactions[array_key_first($this->transactions)];

        if ($this->mgpgResponseService->isAuth3Dv2($nextAction)) {
            $mainTransaction->setThreeDVersion(2);
            $mainTransaction->setThreeDStepUpUrl($nextAction->threeDS2->stepUpUrl);
            $mainTransaction->setThreeDStepUpJwt($this->createThreeDCompleteUrl($nextAction->threeDS2->stepUpJWT));
            $mainTransaction->setMd($nextAction->threeDS2->md);
            $mainTransaction->setThreeDFrictionless(false);
        }

        return NextActionProcessFactory::create(
            $this->createNextActionState($nextAction),
            $this->createThreeDAuthenticateUrl($nextAction),
            $this->getThirdParty($nextAction),
            $this->mgpgResponseService->isRedirectUrl($nextAction),
            $mainTransaction->deviceCollectionUrl(),
            $mainTransaction->deviceCollectionJwt(),
            $mainTransaction,
            $nextAction->resolution ?? null,
            $nextAction->reason ?? null
        );
    }

    /**
     * @param string $termUrl Url returned on process.nextAction when Auth 3D is triggered
     * @return string PG Complete 3D Adaptor url
     */
    protected function createThreeDCompleteUrl(string $termUrl): string
    {
        $jwt = (string) $this->tokenGenerator->generateWithGenericKey(
            [
                JsonWebToken::SESSION_KEY => $this->cryptService->encrypt((string) $this->sessionId),
                'publicKeyIndex'          => $this->cryptService->encrypt((string) $this->publicKeyIndex),
                'returnUrl'               => $this->cryptService->encrypt($this->command->getReturnUrl()),
                'postbackUrl'             => $this->cryptService->encrypt($this->command->getPostbackUrl()),
                'mgpgSessionId'           => $this->cryptService->encrypt($this->command->getMgpgSessionId()),
                'correlationId'           => $this->cryptService->encrypt($this->command->getCorrelationId()),
                'termUrl'                 => $termUrl,
            ]
        );

        return route(
            'mgpg.threed.complete',
            $urlParams = [
                'jwt' => $jwt
            ]
        );
    }

    /**
     * @param NextAction $nextAction
     * @return AbstractState
     * @throws IllegalStateTransitionException
     */
    protected function createNextActionState(NextAction $nextAction): AbstractState
    {
        if ($this->mgpgResponseService->isAuth3D($nextAction)
            || $this->mgpgResponseService->isAuth3Dv2($nextAction)) {
            return ThreeDLookupPerformed::create();
        }

        if ($this->mgpgResponseService->isDeviceDetection($nextAction)) {
            return Pending::create();
        }

        if ($this->mgpgResponseService->isRedirectUrl($nextAction)
            || $this->mgpgResponseService->isRenderGateway($nextAction)) {
            return Valid::create();
        }

        return Processed::create();
    }

    /**
     * @param NextAction $nextAction MGPG NextAction
     * @return null|string
     */
    protected function createThreeDAuthenticateUrl(NextAction $nextAction): ?string
    {
        $payload = [];
        if ($this->mgpgResponseService->isAuth3D($nextAction)) {
            $payload = [
                JsonWebToken::SESSION_KEY => $this->cryptService->encrypt($this->sessionId->value()->toString()),
                'authenticateUrl'         => $this->cryptService->encrypt($nextAction->threeD->authenticateUrl),
                'paReq'                   => $this->cryptService->encrypt($nextAction->threeD->paReq),
                'termUrl'                 => $this->createThreeDCompleteUrl($nextAction->threeD->termUrl)
            ];
        }

        if ($this->mgpgResponseService->isAuth3Dv2($nextAction)) {
            $payload = [
                JsonWebToken::SESSION_KEY => $this->cryptService->encrypt($this->sessionId->value()->toString()),
                'authenticateUrl'         => $this->cryptService->encrypt($nextAction->threeDS2->stepUpUrl),
                'md'                      => $this->cryptService->encrypt($nextAction->threeDS2->md),
                'termUrl'                 => $this->createThreeDCompleteUrl($nextAction->threeDS2->stepUpJWT)
            ];
        }

        if ($this->mgpgResponseService->isRedirectUrl($nextAction)) {
            return $nextAction->thirdParty->url;
        }

        if (empty($payload)) {
            return null;
        }

        return route(
            'mgpg.threed.authenticate',
            $urlParams = [
                'jwt' => (string) $this->tokenGenerator->generateWithGenericKey($payload)
            ]
        );
    }

    /**
     * @param NextAction $nextAction MGPG NextAction
     * @return ThirdParty
     */
    public function getThirdParty(NextAction $nextAction): ?ThirdParty
    {
        if (isset($nextAction->thirdParty) && $nextAction->thirdParty->url) {
            return ThirdParty::create($nextAction->thirdParty->url);
        }

        return null;
    }

    protected function createFraud(NextAction $nextAction): FraudAdvice
    {
        if ($this->mgpgResponseService->blockedDueToFraudAdvice($nextAction)) {
            $this->setFraudRecommendation(
                FraudRecommendation::create(
                    (int) $nextAction->reasonDetails->code,
                    $nextAction->reasonDetails->severity,
                    $nextAction->reasonDetails->message
                )
            );
        }

        return $this->mgpgResponseService->translateFraudAdviceProcessStep($nextAction);
    }

    /**
     * @param array $charges MGPG Charges
     * @return InitializedItemCollection
     * @throws \Exception
     */
    protected function createInitializedItems(array /*<Charge[]>*/ $charges): InitializedItemCollection
    {
        $items = [];

        foreach ($charges as $charge) {
            array_push(
                $items,
                $this->createInitializedItem($charge)
            );
        }

        return new InitializedItemCollection($items);
    }

    /**
     * @param Charge $charge MGPG Charge
     * @return InitializedItem
     * @throws \Exception
     */
    protected function createInitializedItem(Charge $charge): InitializedItem
    {
        // NG Init does one Charge containing one Item, so we expect to
        // have a mgpg process response where each Charge has a single Item.
        $item = $charge->items[0];

        // Each item will only have one entitlement, created during Init call.
        $entitlement = $item->entitlements[0];
        $isTrial = false;
        if(isset($charge->isTrial)) {
            $isTrial = $charge->isTrial;
        }
        
        $initializedItem = InitializedItem::restore(
            ItemId::createFromString($charge->transactionId),
            SiteId::createFromString($charge->siteId),
            BundleId::createFromString($item->skuId), // Init sends bundleId as skuId, we get the same back
            AddonId::createFromString($entitlement['memberProfile']['data']['addonId']),
            $this->createBundleInformation($item),
            $this->createItemTaxInformation($item),
            $charge->isPrimaryCharge == false,
            $isTrial,
            $entitlement['memberProfile']['data']['subscriptionId'] ?? null,
            $this->isCrossSaleSelected($charge),
            false           //TODO when MGPG handles NSF
        );

        if (isset($this->transactions[$charge->transactionId])) {
            $initializedItem->transactionCollection()->add($this->transactions[$charge->transactionId]);
        }

        return $initializedItem;
    }

    /**
     * @param Item $item MGPG Item(provided from within a Charge)
     * @return BundleChargeInformation
     * @throws Exception
     * @throws InvalidAmountException
     */
    protected function createBundleInformation(
        Item $item
    ): BundleChargeInformation {
        if ($item->rebill) {
            return BundleRebillChargeInformation::create(
                Amount::create($item->priceInfo->finalPrice),
                Duration::create($item->priceInfo->expiresInDays),
                TaxBreakdown::create(
                    Amount::create($item->priceInfo->basePrice),
                    Amount::create($item->priceInfo->taxes ?? 0),
                    Amount::create($item->priceInfo->finalPrice)
                ),
                Amount::create($item->rebill->finalPrice),
                Duration::create($item->rebill->rebillDays),
                TaxBreakdown::create(
                    Amount::create($item->rebill->basePrice),
                    Amount::create($item->rebill->taxes ?? 0),
                    Amount::create($item->rebill->finalPrice)
                )
            );
        }

        return BundleSingleChargeInformation::create(
            Amount::create($item->priceInfo->finalPrice),
            Duration::create($item->priceInfo->expiresInDays),
            TaxBreakdown::create(
                Amount::create($item->priceInfo->basePrice),
                Amount::create($item->priceInfo->taxes ?? 0),
                Amount::create($item->priceInfo->finalPrice)
            )
        );
    }

    /**
     * @param Item $item MGPG Charge Item
     * @return null|TaxInformation
     */
    protected function createItemTaxInformation(Item $item): ?TaxInformation
    {
        if ($item->tax) {
            return TaxInformation::create(
                $item->tax->taxName,
                Amount::create($item->tax->taxRate),
                $item->tax->taxApplicationId,
                null,
                TaxType::create($item->tax->taxType)
            );
        }
        return null;
    }

    /**
     * Use site/bundle/addon -> mgpg chargeId map provided by init to
     * @param Charge $charge
     * @return bool
     */
    protected function isCrossSaleSelected(Charge $charge): bool
    {
        return (in_array($charge->chargeId, $this->command->getSelectedChargeIds()));
    }

    /**
     * Maybe use function in PurchaseService instead
     * @return Purchase
     * @throws \Exception
     */
    protected function createPurchase(): Purchase
    {
        $processedItemsCollection = new ProcessedItemsCollection();

        foreach ($this->initializedItemCollection as $itemId => $item) {
            $addonCollection = new AddonCollection();
            $addonCollection->offsetSet((string) $item->itemId(), $item->addonId());
            $processedItemsCollection->offsetSet(
                (string) $item->itemId(),
                ProcessedBundleItem::create(
                    SubscriptionInfo::create(
                        $item->buildSubscriptionId(),
                        (string) "unknown username"
                    ),
                    $item->itemId(),
                    $item->transactionCollection(),
                    $item->bundleId(),
                    $addonCollection,
                    $item->isCrossSale()
                )
            );
        }

        return Purchase::create(
            $this->buildPurchaseId(),
            MemberId::createFromString($this->memberId),
            $this->sessionId,
            $processedItemsCollection,
            null // No state since MGPG is doing the processing
        );
    }

    /**
     * @return bool
     */
    public function isCurrentBillerAvailablePaymentsMethods(): bool
    {
        return true; // TODO
    }

    /**
     * @return array
     */
    public function nextAction(): array
    {
        return $this->nextAction->toArray();
    }

    public function mgpgSessionId(): string
    {
        return $this->command->getMgpgSessionId();
    }

    public function correlationId()
    {
        return $this->command->getCorrelationId();
    }

    /**
     * @return bool
     */
    public function isGiftcardsCompleteProcess(): bool
    {
        return (
            $this->isGiftcards()
            && $this->command instanceof CompletedProcessPurchaseCommand
        );
    }

    /**
     * @return bool
     */
    protected function isGiftcards(): bool
    {
        $hasGiftcards = false;
        if (!empty($this->paymentInfo)) {
            $hasGiftcards = $this->paymentInfo->paymentMethod() === PaymentMethod::GIFTCARDS &&
                $this->paymentInfo->paymentType() === PaymentType::GIFTCARDS;
        }
        return $hasGiftcards;
    }

    /**
     * @param string $errorType
     *
     * @return bool
     */
    protected function isNSF(string $errorType): bool
    {
        return $errorType == Code::ERROR_CLASSIFICATION_NSF;
    }

    /**
     * @return bool|null
     */
    public function checkForDeclinedAndNsfTransaction(): ?bool
    {
        $result = null;
        foreach ($this->transactions as $transaction) {
            if ($transaction->state() === Transaction::STATUS_DECLINED) {
                $result = false;
                if ($transaction->isNsf()) {
                    return true;
                }
            }
        }

        return $result;
    }
}
