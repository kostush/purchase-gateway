<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\Exceptions\UnknownBillerNameException;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\ManageCreditCardBlacklistTrait;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\AttemptTransactionData;
use ProBillerNG\PurchaseGateway\Domain\Model\Biller;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\BinRouting;
use ProBillerNG\PurchaseGateway\Domain\Model\BinRoutingCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\ChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\ChequePaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\ExistingCCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\QyssoBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\UnableToCompleteThreeDTransactionException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\BillerInteraction;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\AbortTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\CCTransactionInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\NewCCTransactionInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RocketgateCCRetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\ThirdPartyRebillTransaction;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\TransactionInformation;
use Throwable;

class TransactionService
{
    use ManageCreditCardBlacklistTrait;

    /**
     * @var TransactionTranslatingService
     */
    private $transactionTranslatingService;

    /**
     * @var TokenGenerator
     */
    private $tokenGenerator;

    /**
     * @var CryptService
     */
    protected $cryptService;

    /**
     * TransactionService constructor.
     * @param TransactionTranslatingService $transactionTranslatingService The transaction translating service
     * @param TokenGenerator                $tokenGenerator                Token generator
     * @param CryptService                  $cryptService                  Crypt service
     */
    public function __construct(
        TransactionTranslatingService $transactionTranslatingService,
        TokenGenerator $tokenGenerator,
        CryptService $cryptService
    ) {
        $this->transactionTranslatingService = $transactionTranslatingService;
        $this->tokenGenerator                = $tokenGenerator;
        $this->cryptService                  = $cryptService;
    }

    /**
     * @param InitializedItem        $mainPurchase           Main purchase
     * @param array                  $crossSales             Cross sales
     * @param BillerMapping          $billerMapping          Biller mapping
     * @param BinRoutingCollection   $binRoutingCollection   Bin routing collection
     * @param Biller                 $biller                 Biller
     * @param AttemptTransactionData $attemptTransactionData Attempt transaction data
     * @param FraudAdvice            $fraudAdvice            Fraud Advice
     * @param Site                   $site                   Site
     * @return void
     * @throws Exception
     * @throws \Exception
     */
    public function attemptTransactions(
        InitializedItem $mainPurchase,
        array $crossSales,
        BillerMapping $billerMapping,
        BinRoutingCollection $binRoutingCollection,
        Biller $biller,
        AttemptTransactionData $attemptTransactionData,
        FraudAdvice $fraudAdvice,
        Site $site
    ): void {
        $this->attemptMainTransaction(
            $mainPurchase,
            $billerMapping,
            $binRoutingCollection,
            $biller,
            $attemptTransactionData,
            $fraudAdvice,
            $site
        );

        $this->attemptCrossSaleTransactions(
            $mainPurchase,
            $crossSales,
            $billerMapping,
            $biller,
            $attemptTransactionData,
            $fraudAdvice,
            $site
        );
    }

    /**
     * @param InitializedItem        $mainPurchase           Main purchase
     * @param BillerMapping          $billerMapping          Biller mapping
     * @param BinRoutingCollection   $binRoutingCollection   Bin routing collection
     * @param Biller                 $biller                 Biller
     * @param AttemptTransactionData $attemptTransactionData Attempt transaction data
     * @param FraudAdvice            $fraudAdvice            Fraud Advice
     * @param Site                   $site                   Site
     * @return void
     * @throws Exception
     * @throws \Exception
     */
    protected function attemptMainTransaction(
        InitializedItem $mainPurchase,
        BillerMapping $billerMapping,
        BinRoutingCollection $binRoutingCollection,
        Biller $biller,
        AttemptTransactionData $attemptTransactionData,
        FraudAdvice $fraudAdvice,
        Site $site
    ): void {
        Log::info('Attempting the main transaction');

        $mainTransactions = $this->attemptTransactionWithBinRouting(
            $mainPurchase,
            $binRoutingCollection,
            $biller,
            $attemptTransactionData,
            $billerMapping,
            $fraudAdvice,
            $site
        );

        $this->addTransactionsToCollection($mainTransactions, $mainPurchase);
    }

    /**
     * @param InitializedItem        $mainPurchase           Main purchase
     * @param array                  $crossSales             Cross sales
     * @param BillerMapping          $billerMapping          Biller mapping
     * @param Biller                 $biller                 Biller
     * @param AttemptTransactionData $attemptTransactionData Attempt transaction data
     * @param FraudAdvice            $fraudAdvice            Fraud Advice
     * @param Site                   $site                   Site
     * @return void
     *
     * @throws \Exception
     */
    protected function attemptCrossSaleTransactions(
        InitializedItem $mainPurchase,
        array $crossSales,
        BillerMapping $billerMapping,
        Biller $biller,
        AttemptTransactionData $attemptTransactionData,
        FraudAdvice $fraudAdvice,
        Site $site
    ): void {
        if (!$mainPurchase->wasItemPurchaseSuccessful()) {
            return;
        }

        // We only need to add the bin routing into the collection if it is not null.
        $binRoutingElements   = [];
        $successfulBinRouting = $mainPurchase->transactionCollection()->last()->successfulBinRouting();
        if (!empty($successfulBinRouting)) {
            $binRoutingElements = [$successfulBinRouting];
        }

        $binRoutingCollection = new BinRoutingCollection($binRoutingElements);

        $billerMappingForCrossSale = $this->shouldAddBillerFraudBypassFlag($biller, $billerMapping);

        /** @var InitializedItem $crossSale */
        foreach ($crossSales as $crossSale) {
            $crossSaleTransactions = $this->attemptTransactionWithBinRouting(
                $crossSale,
                $binRoutingCollection,
                $biller,
                $attemptTransactionData,
                $billerMappingForCrossSale,
                $fraudAdvice,
                $site
            );

            $this->addTransactionsToCollection($crossSaleTransactions, $crossSale);
        }
    }

    /**
     * We are appending the biller fraud flag for cross-sale for netbilling
     * @param Biller        $biller        Biller
     * @param BillerMapping $billerMapping Biller Mapping
     * @return BillerMapping  BillerMapping
     */
    protected function shouldAddBillerFraudBypassFlag(Biller $biller, BillerMapping $billerMapping): BillerMapping
    {
        if ($biller instanceof NetbillingBiller) {
            $billerMapping->billerFields()->setDisableFraudChecks(true);
        }

        return $billerMapping;
    }

    /**
     * @param InitializedItem        $item                   The initialized item
     * @param BinRoutingCollection   $binRoutingCollection   The bin routing collection
     * @param Biller                 $biller                 The biller
     * @param AttemptTransactionData $attemptTransactionData Attempt transaction data
     * @param BillerMapping          $billerMapping          The biller mapping
     * @param FraudAdvice            $fraudAdvice            Fraud Advice
     * @param Site                   $site                   Site
     * @return array
     * @throws Exception
     * @throws \Exception
     */
    protected function attemptTransactionWithBinRouting(
        InitializedItem $item,
        BinRoutingCollection $binRoutingCollection,
        Biller $biller,
        AttemptTransactionData $attemptTransactionData,
        BillerMapping $billerMapping,
        FraudAdvice $fraudAdvice,
        Site $site
    ): array {
        $attemptedTransactions = [];
        $shouldUseThreeD       = $this->shouldUseThreeD($biller, $fraudAdvice, $item);
        $returnUrl             = $this->returnUrl();

        Log::info(
            'AttemptWithBinRouting Starting attempts process',
            [
                'numberOfBinRoutings' => $binRoutingCollection->count(),
                'numberOfAttempts'    => $site->attempts()
            ]
        );

        Log::info(
            'AttemptWithBinRouting Should use 3ds on transaction service request.',
            [
                'shouldUseThreeD' => $shouldUseThreeD
            ]
        );

        if ($binRoutingCollection->count() === 0) {
            Log::info(
                'AttemptWithBinRouting BinRouting list is empty, ignoring numberOfAttempts (' . $site->attempts() . ') and doing 1/1 attempt',
                [
                    'numberOfBinRoutings' => $binRoutingCollection->count(),
                    'numberOfAttempts'    => $site->attempts()
                ]
            );

            return [
                $this->performTransaction(
                    $item->siteId(),
                    null,
                    $biller,
                    $attemptTransactionData,
                    $item->chargeInformation(),
                    $billerMapping,
                    $shouldUseThreeD,
                    $returnUrl,
                    $item->isNSFSupported()
                )
            ];
        }

        // if routing codes exist, try to perform purchase them
        // TODO - improve the logic here as well. A better approach would be to actually iterate over the bin routing
        // advice like $maxAttempts = binRoutingCollection->count() ?? 1
        $stopOnFailure = false;
        for ($attempt = 1; $attempt <= $site->attempts(); $attempt++) {
            Log::info(
                'AttemptWithBinRouting execution of performTransaction: ' . $attempt . '/' . $site->attempts() . ' attempt',
                [
                    'numberOfBinRoutings' => $binRoutingCollection->count(),
                    'numberOfAttempts'    => $site->attempts()
                ]
            );

            if ($binRoutingCollection->count() === 1) {
                // If I only have one it means that use it independent of the index as right now it would mean I am
                // using the one that was used successfully with the previous transaction
                $binRouting = $binRoutingCollection->first();
                // We will only attempt the cross sale once, with the bin routing of the main item
                $stopOnFailure = true;
            } else {
                // TODO  - Remove this code. We should not index items per the item and specially not like this
                // the logic is duplicated - when we saved there and when we are retrieving. Now I had to fix and
                // instead of doing I had to keep
                $binRouting = $binRoutingCollection->offsetGet((string) $item->itemId() . '_' . $attempt);
            }

            // If the item does not have routing codes, no point in calling the service twice
            if (empty($binRouting)) {
                Log::info(
                    'AttemptWithBinRouting Current binRouting is empty, doing ' . $attempt . '/' . $site->attempts() . ' attempt and stopping after',
                    [
                        'numberOfBinRoutings' => $binRoutingCollection->count(),
                        'numberOfAttempts'    => $site->attempts()
                    ]
                );

                $attemptedTransactions[] = $this->performTransaction(
                    $item->siteId(),
                    null,
                    $biller,
                    $attemptTransactionData,
                    $item->chargeInformation(),
                    $billerMapping,
                    $shouldUseThreeD,
                    $returnUrl,
                    $item->isNSFSupported()
                );

                break;
            }

            $transaction = $this->performTransaction(
                $item->siteId(),
                $binRouting,
                $biller,
                $attemptTransactionData,
                $item->chargeInformation(),
                $billerMapping,
                $shouldUseThreeD,
                $returnUrl,
                $item->isNSFSupported()
            );

            $attemptedTransactions[] = $transaction;

            if ($transaction->isApproved()
                || $transaction->isPending()
                || $stopOnFailure
                || ($transaction->errorClassificationHasHardType() && $this->isBlacklistCreditCardFeatureEnabled())
            ) {
                break;
            }
        }

        return $attemptedTransactions;
    }

    /**
     * @param SiteId                 $siteId                 The site id
     * @param BinRouting|null        $binRouting             The bin routing object
     * @param Biller                 $biller                 The biller
     * @param AttemptTransactionData $attemptTransactionData Attempt transaction data
     * @param ChargeInformation      $chargeInformation      The charge information
     * @param BillerMapping          $billerMapping          The biller mapping
     * @param bool                   $useThreeD              Perform transaction using 3DS
     * @param string|null            $returnUrl              Return URL
     * @param bool                   $isNSFSupported         Flag to show if NSF is supported or not
     *
     * @return Transaction
     * @throws \Exception
     */
    protected function performTransaction(
        SiteId $siteId,
        ?BinRouting $binRouting,
        Biller $biller,
        AttemptTransactionData $attemptTransactionData,
        ChargeInformation $chargeInformation,
        BillerMapping $billerMapping,
        bool $useThreeD = false,
        ?string $returnUrl = null,
        bool $isNSFSupported = false
    ): Transaction {

        if ($attemptTransactionData->paymentInfo() instanceof ExistingCCPaymentInfo) {
            if ($billerMapping->billerFields() instanceof RocketgateBillerFields
                && $billerMapping->billerFields()->simplified3DS() === false
            ) {
                $useThreeD = false;
            }

            return $this->performTransactionWithExistingCard(
                $siteId,
                $binRouting,
                $biller,
                $attemptTransactionData,
                $chargeInformation,
                $billerMapping,
                $useThreeD,
                $returnUrl
            );
        }

        if ($attemptTransactionData->paymentInfo() instanceof ChequePaymentInfo) {
            return $this->performTransactionWithCheque(
                $siteId,
                $biller,
                $attemptTransactionData,
                $chargeInformation,
                $billerMapping
            );
        }

        return $this->performTransactionWithNewCard(
            $siteId,
            $binRouting,
            $biller,
            $attemptTransactionData,
            $chargeInformation,
            $billerMapping,
            $useThreeD,
            $returnUrl,
            $isNSFSupported
        );
    }


    /**
     * @param TransactionId $transactionId Transaction id
     * @param SessionId     $sessionId     Session id
     * @return RetrieveTransactionResult
     * @throws \Exception
     */
    public function getTransactionDataBy(TransactionId $transactionId, SessionId $sessionId): RetrieveTransactionResult
    {
        try {
            return $this->transactionTranslatingService->getTransactionDataBy($transactionId, $sessionId);
        } catch (\Exception $e) {
            Log::info('Unable to get transaction data', ['transactionId' => (string) $transactionId]);

            throw $e;
        }
    }

    /**
     * @param SiteId                 $siteId                 SiteId
     * @param Biller                 $biller                 Biller
     * @param AttemptTransactionData $attemptTransactionData AttemptTransactionData
     * @param ChargeInformation      $chargeInformation      ChargeInformation
     * @param BillerMapping          $billerMapping          BillerMapping
     *
     * @return Transaction
     * @throws \Exception
     */
    protected function performTransactionWithCheque(
        SiteId $siteId,
        Biller $biller,
        AttemptTransactionData $attemptTransactionData,
        ChargeInformation $chargeInformation,
        BillerMapping $billerMapping
    ): Transaction {
        return $this->transactionTranslatingService->performTransactionWithCheque(
            $siteId,
            $biller,
            $attemptTransactionData->currency(),
            $attemptTransactionData->userInfo(),
            $chargeInformation,
            $attemptTransactionData->paymentInfo(),
            $billerMapping,
            SessionId::createFromString(Log::getSessionId())
        );
    }

    /**
     * @param SiteId                 $siteId                 The site id
     * @param BinRouting|null        $binRouting             The bin routing object
     * @param Biller                 $biller                 The biller
     * @param AttemptTransactionData $attemptTransactionData Attempt transaction data
     * @param ChargeInformation      $chargeInformation      The charge information
     * @param BillerMapping          $billerMapping          The biller mapping
     * @param bool                   $useThreeD              Perform transaction using 3DS
     * @param string|null            $returnUrl              Probiller return URL
     * @param bool                   $isNSFSupported         Flag to show if NSF is supported or not
     *
     * @return Transaction
     * @throws \Exception
     */
    protected function performTransactionWithNewCard(
        SiteId $siteId,
        ?BinRouting $binRouting,
        Biller $biller,
        AttemptTransactionData $attemptTransactionData,
        ChargeInformation $chargeInformation,
        BillerMapping $billerMapping,
        bool $useThreeD = false,
        ?string $returnUrl = null,
        bool $isNSFSupported = false
    ): Transaction {
        return $this->transactionTranslatingService->performTransactionWithNewCard(
            $siteId,
            $biller,
            $attemptTransactionData->currency(),
            $attemptTransactionData->userInfo(),
            $chargeInformation,
            $attemptTransactionData->paymentInfo(),
            $billerMapping,
            SessionId::createFromString(Log::getSessionId()),
            $binRouting,
            $useThreeD,
            $returnUrl,
            $isNSFSupported
        );
    }

    /**
     * @param SiteId                 $siteId                 The site id
     * @param BinRouting|null        $binRouting             The bin routing object
     * @param Biller                 $biller                 The biller
     * @param AttemptTransactionData $attemptTransactionData Attempt transaction data
     * @param ChargeInformation      $chargeInformation      The charge information
     * @param BillerMapping          $billerMapping          The biller mapping
     *
     * @param bool                   $useThreeD              Use threeD flag
     * @param string|null            $returnUrl              Return URL
     * @return Transaction
     * @throws \Exception
     */
    protected function performTransactionWithExistingCard(
        SiteId $siteId,
        ?BinRouting $binRouting,
        Biller $biller,
        AttemptTransactionData $attemptTransactionData,
        ChargeInformation $chargeInformation,
        BillerMapping $billerMapping,
        bool $useThreeD = false,
        ?string $returnUrl = null
    ): Transaction {
        return $this->transactionTranslatingService->performTransactionWithExistingCard(
            $siteId,
            $biller,
            $attemptTransactionData->currency(),
            $attemptTransactionData->userInfo(),
            $chargeInformation,
            $attemptTransactionData->paymentInfo(),
            $billerMapping,
            SessionId::createFromString(Log::getSessionId()),
            $binRouting,
            $useThreeD,
            $returnUrl
        );
    }

    /**
     * @param array           $attemptedTransactions Array of attempted transactions
     * @param InitializedItem $purchase              Purchase
     * @return void
     */
    protected function addTransactionsToCollection(
        array $attemptedTransactions,
        InitializedItem $purchase
    ): void {
        foreach ($attemptedTransactions as $attemptedTransaction) {
            $purchase->transactionCollection()->add($attemptedTransaction);
        }
    }

    /**
     * @param Biller          $biller      Biller
     * @param FraudAdvice     $fraudAdvice Fraud Advice
     * @param InitializedItem $item        Item being processed
     * @return bool
     * @throws Exception
     */
    private function shouldUseThreeD(Biller $biller, FraudAdvice $fraudAdvice, InitializedItem $item): bool
    {
        $useThreeD = false;

        Log::info(
            'ShouldUseThreeD Fields',
            [
                'isThreeDSupported' => $biller->isThreeDSupported(),
                'isForceThreeD'     => $fraudAdvice->isForceThreeD(),
                'isCrossSale'       => $item->isCrossSale()
            ]
        );

        if ($biller->isThreeDSupported() && $fraudAdvice->isForceThreeD() && !$item->isCrossSale()) {
            $useThreeD = true;
        }

        return $useThreeD;
    }

    /**
     * @param TransactionId $transactionId Transaction id.
     * @param string|null   $pares         Pares.
     * @param string|null   $md            Rocketgate biller transaction id.
     * @param SessionId     $sessionId     Session id.
     * @return Transaction
     * @throws Exception
     * @throws \Exception
     */
    public function performCompleteThreeDTransaction(
        TransactionId $transactionId,
        ?string $pares,
        ?string $md,
        SessionId $sessionId
    ): Transaction {
        try {
            return $this->transactionTranslatingService->performCompleteThreeDTransaction(
                $transactionId,
                $pares,
                $md,
                $sessionId
            );
        } catch (\Exception $e) {
            Log::info(
                'Unable to perform complete threeD transaction',
                [
                    'transactionId' => (string) $transactionId,
                    'pares'         => (string) $pares,
                    'md'            => (string) $md
                ]
            );

            throw $e;
        }
    }

    /**
     * @param TransactionId $transactionId Transaction id.
     * @param string        $queryString   Query string.
     * @param SessionId     $sessionId     Session id.
     * @return Transaction
     * @throws Exception
     */
    public function performSimplifiedCompleteThreeDTransaction(
        TransactionId $transactionId,
        string $queryString,
        SessionId $sessionId
    ): Transaction {
        try {
            return $this->transactionTranslatingService->performSimplifiedCompleteThreeDTransaction(
                $transactionId,
                $queryString,
                $sessionId
            );
        } catch (\Exception $e) {
            Log::info(
                'Unable to perform the simplified complete threeD transaction',
                [
                    'transactionId' => (string) $transactionId,
                    'queryString'   => $queryString
                ]
            );

            throw $e;
        }
    }

    /**
     * @param InitializedItem $mainPurchase  Main purchase.
     * @param array           $crossSales    Cross sales.
     * @param Site            $site          Site.
     * @param FraudAdvice     $fraudAdvice   Fraud service.
     * @param UserInfo        $userInfo      User info.
     * @param SessionId       $sessionId     Session id.
     * @param string|null     $pares         Pares.
     * @param string|null     $md            Rocketgate biller transaction id.
     * @param string|null     $paymentMethod Payment method
     * @return NewCCTransactionInformation|null
     * @throws Exception
     * @throws Throwable
     */
    public function attemptCompleteThreeDTransaction(
        InitializedItem $mainPurchase,
        array $crossSales,
        Site $site,
        ?FraudAdvice $fraudAdvice,
        UserInfo $userInfo,
        SessionId $sessionId,
        ?string $pares,
        ?string $md,
        ?string $paymentMethod
    ): ?NewCCTransactionInformation {

        try {
            $transaction = $this->performCompleteThreeDTransaction(
                $mainPurchase->lastTransactionId(),
                $pares,
                $md,
                $sessionId
            );

            $transaction->setThreeDVersion($mainPurchase->lastTransaction()->threeDVersion());

            $this->addTransactionsToCollection([$transaction], $mainPurchase);

            if ($transaction->isAborted()) {
                throw new UnableToCompleteThreeDTransactionException();
            }

            $fullTransactionData = $this->getTransactionDataBy($transaction->transactionId(), $sessionId);

            if (!$mainPurchase->wasItemPurchaseSuccessful() || empty($crossSales)) {
                return $fullTransactionData->transactionInformation();
            }

            if ($fullTransactionData instanceof RocketgateCCRetrieveTransactionResult
                && !empty($fullTransactionData->merchantAccount())
            ) {
                $binRouting = BinRouting::create(1, $fullTransactionData->merchantAccount());
                $mainPurchase->transactionCollection()->last()->addSuccessfulBinRouting($binRouting);
            }

            try {
                $this->attemptCrossSaleTransactions(
                    $mainPurchase,
                    $crossSales,
                    BillerMapping::create(
                        $site->siteId(),
                        $site->businessGroupId(),
                        $fullTransactionData->currency(),
                        $transaction->billerName(),
                        CrossSaleBillerFieldsFactory::create(
                            $fullTransactionData->billerFields(),
                            $transaction->billerName()
                        )
                    ),
                    BillerFactoryService::create($transaction->billerName()),
                    AttemptTransactionData::create(
                        CurrencyCode::create($fullTransactionData->currency()),
                        $userInfo,
                        ExistingCCPaymentInfo::create(
                            $fullTransactionData->cardHash(),
                            null,
                            $paymentMethod,
                            []
                        )
                    ),
                    $fraudAdvice,
                    $site
                );
            } catch (Throwable $e) {
                Log::info('Unable to perform crossSale transaction on 3DS complete');
            }
        } catch (\Exception $e) {
            Log::info(
                'Unable to perform complete threeD transaction',
                [
                    'transactionId' => (string) $mainPurchase->lastTransactionId(),
                    'pares'         => (string) $pares,
                    'md'            => (string) $md
                ]
            );

            throw $e;
        }

        return $fullTransactionData->transactionInformation();
    }

    /**
     * @param InitializedItem  $mainPurchase  Main purchase
     * @param array            $crossSales    Cross sales
     * @param Site             $site          Site
     * @param FraudAdvice|null $fraudAdvice   Fraud advice
     * @param UserInfo         $userInfo      User info
     * @param SessionId        $sessionId     Session Id
     * @param string           $queryString   Query string
     * @param string|null      $paymentMethod Payment method
     * @return CCTransactionInformation|null
     * @throws Exception
     * @throws \Exception
     */
    public function attemptSimplifiedCompleteThreeDTransaction(
        InitializedItem $mainPurchase,
        array $crossSales,
        Site $site,
        ?FraudAdvice $fraudAdvice,
        UserInfo $userInfo,
        SessionId $sessionId,
        string $queryString,
        ?string $paymentMethod
    ): ?CCTransactionInformation {
        try {
            $transaction = $this->performSimplifiedCompleteThreeDTransaction(
                $mainPurchase->lastTransactionId(),
                $queryString,
                $sessionId
            );

            $this->addTransactionsToCollection([$transaction], $mainPurchase);

            if ($transaction->isAborted()) {
                throw new UnableToCompleteThreeDTransactionException();
            }

            $fullTransactionData = $this->getTransactionDataBy($transaction->transactionId(), $sessionId);

            $this->setWhetherANewCCWasUsedOrNot($transaction, $fullTransactionData->transactionInformation());

            if (empty($crossSales) || !$mainPurchase->wasItemPurchaseSuccessful()) {
                return $fullTransactionData->transactionInformation();
            }

            if ($fullTransactionData instanceof RocketgateCCRetrieveTransactionResult
                && !empty($fullTransactionData->merchantAccount())
            ) {
                $binRouting = BinRouting::create(1, $fullTransactionData->merchantAccount());
                $mainPurchase->transactionCollection()->last()->addSuccessfulBinRouting($binRouting);
            }

            try {
                $this->attemptCrossSaleTransactions(
                    $mainPurchase,
                    $crossSales,
                    BillerMapping::create(
                        $site->siteId(),
                        $site->businessGroupId(),
                        $fullTransactionData->currency(),
                        $transaction->billerName(),
                        CrossSaleBillerFieldsFactory::create(
                            $fullTransactionData->billerFields(),
                            $transaction->billerName()
                        )
                    ),
                    BillerFactoryService::create($transaction->billerName()),
                    AttemptTransactionData::create(
                        CurrencyCode::create($fullTransactionData->currency()),
                        $userInfo,
                        ExistingCCPaymentInfo::create(
                            $fullTransactionData->cardHash(),
                            null,
                            $paymentMethod,
                            []
                        )
                    ),
                    $fraudAdvice,
                    $site
                );
            } catch (Throwable $e) {
                Log::info('Unable to perform crossSale transaction on 3DS simplified complete');
            }
        } catch (\Exception $e) {
            Log::info(
                'Unable to perform the simplified complete threeD transaction',
                [
                    'transactionId' => (string) $mainPurchase->lastTransactionId(),
                    'queryString'   => $queryString
                ]
            );

            throw $e;
        }

        return $fullTransactionData->transactionInformation();
    }

    /**
     * @param TransactionId $transactionId Transaction id
     * @param string        $billerName    Biller name
     * @param SessionId     $sessionId     Session id
     * @param array         $returnPayload Return payload
     * @return BillerInteraction
     * @throws Exception
     * @throws \Exception
     */
    public function addBillerInteraction(
        TransactionId $transactionId,
        string $billerName,
        SessionId $sessionId,
        array $returnPayload
    ): BillerInteraction {
        try {
            switch ($billerName) {
                case EpochBiller::BILLER_NAME:
                    return $this->transactionTranslatingService->addEpochBillerInteraction(
                        $transactionId,
                        $sessionId,
                        $returnPayload
                    );
                case QyssoBiller::BILLER_NAME:
                    return $this->transactionTranslatingService->addQyssoBillerInteraction(
                        $transactionId,
                        $sessionId,
                        $returnPayload
                    );
                default:
                    throw new UnknownBillerNameException($billerName);
            }
        } catch (\Exception $e) {
            Log::info('Unable to add biller interaction', ['transactionId' => (string) $transactionId]);
            throw $e;
        }
    }

    /**
     * @param TransactionId $previousTransactionId Previous Transaction id
     * @param string        $billerName            Biller name
     * @param SessionId     $sessionId             Session id
     * @param array         $rebillPayload         Rebill payload
     * @return ThirdPartyRebillTransaction
     * @throws Exception
     * @throws \Exception
     */
    public function createRebillTransaction(
        TransactionId $previousTransactionId,
        string $billerName,
        SessionId $sessionId,
        array $rebillPayload
    ): ThirdPartyRebillTransaction {
        try {
            switch ($billerName) {
                case QyssoBiller::BILLER_NAME:
                    return $this->transactionTranslatingService->createQyssoRebillTransaction(
                        $previousTransactionId,
                        $sessionId,
                        $rebillPayload
                    );
                default:
                    throw new UnknownBillerNameException($billerName);
            }
        } catch (\Exception $e) {
            Log::info(
                'Unable to create rebill transaction',
                ['previousTransactionId' => (string) $previousTransactionId]
            );

            throw $e;
        }
    }

    /**
     * @param InitializedItem        $mainPurchase           Main purchase.
     * @param array                  $crossSales             Cross sales.
     * @param Site                   $site                   Site.
     * @param array                  $crossSaleSites         Cross sales sites.
     * @param Biller                 $biller                 Biller.
     * @param AttemptTransactionData $attemptTransactionData Attempt transaction data.
     * @param BillerMapping          $billerMapping          Biller mapping.
     * @param string                 $redirectUrl            Redirect url.
     * @param string|null            $paymentMethod          Payment method.
     * @param string|null            $billerMemberId         Biller member id.
     * @return void
     * @throws \Exception
     */
    public function performThirdPartyTransaction(
        InitializedItem $mainPurchase,
        array $crossSales,
        Site $site,
        array $crossSaleSites,
        Biller $biller,
        AttemptTransactionData $attemptTransactionData,
        BillerMapping $billerMapping,
        string $redirectUrl,
        ?string $paymentMethod,
        ?string $billerMemberId
    ): void {
        try {
            $crossSalesList = [];

            /** @var InitializedItem $crossSale */
            foreach ($crossSales as $crossSale) {
                $crossSalesList[] = [
                    'chargeInformation' => $crossSale->chargeInformation(),
                    'taxInformation'    => $crossSale->taxInformation(),
                    'id'                => (string) $crossSale->itemId()
                ];
            }

            $transaction = $this->transactionTranslatingService->performTransactionWithThirdParty(
                $site,
                $crossSaleSites,
                $biller,
                $attemptTransactionData->currency(),
                $attemptTransactionData->userInfo(),
                $mainPurchase->chargeInformation(),
                $attemptTransactionData->paymentInfo(),
                $billerMapping,
                SessionId::createFromString(Log::getSessionId()),
                $redirectUrl,
                $mainPurchase->taxInformation(),
                empty($crossSalesList) ? null : $crossSalesList,
                $paymentMethod,
                $billerMemberId
            );

            $mainTransaction = Transaction::create(
                $transaction->transactionId(),
                $transaction->state(),
                $transaction->billerName(),
                null,
                null,
                null,
                $transaction->redirectUrl()
            );

            $this->addTransactionsToCollection([$mainTransaction], $mainPurchase);

            /** @var InitializedItem $crossSale */
            foreach ($crossSales as $key => $crossSale) {
                $transactionCrossSales = $transaction->crossSales();
                if (!isset($transactionCrossSales[$key])) {
                    continue;
                }
                $this->addTransactionsToCollection([$transactionCrossSales[$key]], $crossSale);
            }
        } catch (\Exception $e) {
            Log::info(
                'Unable to perform thirdParty transaction'
            );

            throw $e;
        }
    }

    /**
     * @param TransactionId $transactionId Transaction id
     * @param SessionId     $sessionId     Session id
     * @return AbortTransactionResult
     * @throws \Exception
     */
    public function abortTransaction(TransactionId $transactionId, SessionId $sessionId): AbortTransactionResult
    {
        try {
            return $this->transactionTranslatingService->abortTransaction($transactionId, $sessionId);
        } catch (\Exception $e) {
            Log::info('Unable to abort transaction ', ['transactionId' => (string) $transactionId]);

            throw $e;
        }
    }

    /**
     * @param InitializedItem  $mainPurchase        Main purchase
     * @param PaymentInfo      $paymentInfo         Payment info
     * @param array            $crossSales          Cross sales
     * @param Site             $site                Site
     * @param FraudAdvice|null $fraudAdvice         Fraud advice
     * @param UserInfo         $userInfo            User info
     * @param string           $redirectUrl         Redirect url
     * @param string           $deviceFingerprintId Device fingerprint id
     * @param bool             $isNsfSupported      IsNsfSupported
     * @return Transaction
     * @throws Exception
     * @throws \Exception
     */
    public function lookupTransaction(
        InitializedItem $mainPurchase,
        PaymentInfo $paymentInfo,
        array $crossSales,
        Site $site,
        ?FraudAdvice $fraudAdvice,
        UserInfo $userInfo,
        string $redirectUrl,
        string $deviceFingerprintId,
        bool $isNsfSupported = false
    ): Transaction {
        try {
            $sessionId = SessionId::createFromString(Log::getSessionId());

            $transaction = $this->transactionTranslatingService->performLookupTransaction(
                $mainPurchase->lastTransactionId(),
                $paymentInfo,
                $redirectUrl,
                $deviceFingerprintId,
                $mainPurchase->lastTransaction()->billerName(),
                $sessionId,
                $isNsfSupported
            );

            if ($transaction->isApproved()) {
                $transaction->setThreeDFrictionless(true);
            }

            $this->addTransactionsToCollection([$transaction], $mainPurchase);
            /*
             * frictionless case, transaction was approved
             */
            if (!$mainPurchase->wasItemPurchaseSuccessful() || empty($crossSales)) {
                return $transaction;
            }

            $fullTransactionData = $this->getTransactionDataBy($transaction->transactionId(), $sessionId);

            if ($fullTransactionData instanceof RocketgateCCRetrieveTransactionResult
                && !empty($fullTransactionData->merchantAccount())
            ) {
                $binRouting = BinRouting::create(1, $fullTransactionData->merchantAccount());
                $mainPurchase->transactionCollection()->last()->addSuccessfulBinRouting($binRouting);
            }

            $this->attemptCrossSaleTransactions(
                $mainPurchase,
                $crossSales,
                BillerMapping::create(
                    $site->siteId(),
                    $site->businessGroupId(),
                    $fullTransactionData->currency(),
                    $transaction->billerName(),
                    CrossSaleBillerFieldsFactory::create(
                        $fullTransactionData->billerFields(),
                        $transaction->billerName()
                    )
                ),
                BillerFactoryService::create($transaction->billerName()),
                AttemptTransactionData::create(
                    CurrencyCode::create($fullTransactionData->currency()),
                    $userInfo,
                    ExistingCCPaymentInfo::create(
                        $fullTransactionData->cardHash(),
                        null,
                        $paymentInfo->paymentMethod(),
                        []
                    )
                ),
                $fraudAdvice,
                $site
            );
        } catch (\Exception $e) {
            Log::info(
                'Unable to perform lookup transaction ',
                [
                    'transactionId' => (string) $mainPurchase->lastTransactionId()
                ]
            );

            throw $e;
        }

        return $transaction;
    }

    /**
     * @return string|null
     * @throws \Exception
     */
    private function returnUrl(): ?string
    {
        $jwt = (string) $this->tokenGenerator->generateWithGenericKey(
            [
                'sessionId' => $this->cryptService->encrypt(Log::getSessionId())
            ]
        );

        return route('threed.simplified.complete', ['jwt' => $jwt]);
    }

    /**
     * @param Transaction            $transaction            Transaction
     * @param TransactionInformation $transactionInformation Transaction information
     * @return void
     */
    private function setWhetherANewCCWasUsedOrNot(
        Transaction $transaction,
        TransactionInformation $transactionInformation
    ): void {
        if ($transactionInformation instanceof NewCCTransactionInformation) {
            $newCCUsed = true;
        } else {
            $newCCUsed = false;
        }

        $transaction->setNewCCUsed($newCCUsed);
    }
}
