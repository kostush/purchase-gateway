<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit;

use ProBillerNG\BI\BILoggerService;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\BI\PurchaseInitialized;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Init\PurchaseInitDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\Exceptions\CrossSaleSiteNotExistException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\MissingRedirectUrlException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\NuDataNotFoundException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\UnableToCreatePurchaseProcessException;
use ProBillerNG\PurchaseGateway\Application\FraudIntegrationMapper;
use ProBillerNG\PurchaseGateway\Application\Services\BaseCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\AtlasFields;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerAvailablePaymentMethods;
use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;
use ProBillerNG\PurchaseGateway\Domain\Model\CountryCode;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidAmountException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItemCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Ip;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\RestartProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\CCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Domain\Services\BundleValidationService;
use ProBillerNG\PurchaseGateway\Domain\Services\CascadeService;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\InvalidForceCascadeException;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudService;
use ProBillerNG\PurchaseGateway\Domain\Services\NuDataService;
use ProBillerNG\PurchaseGateway\Domain\Services\PaymentInfoFactoryService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;

abstract class BaseInitCommandHandler extends BaseCommandHandler
{
    /**
     * @var PurchaseProcess
     */
    protected $purchaseProcess;

    /**
     * @var CascadeService
     */
    private $cascadeService;

    /**
     * @var FraudService
     */
    private $fraudService;

    /**
     * @var NuDataService
     */
    private $nuDataService;

    /**
     * @var PurchaseProcessHandler
     */
    protected $purchaseProcessHandler;

    /**
     * @var BILoggerService
     */
    protected $biLoggerService;

    /**
     * @var PurchaseInitDTOAssembler
     */
    protected $dtoAssembler;

    /**
     * @var BundleValidationService
     */
    protected $bundleValidationService;

    /**
     * @var EventIngestionService
     */
    protected $eventIngestionService;

    /** @var ConfigService */
    private $configServiceClient;

    /**
     * BaseInitCommandHandler constructor.
     * @param CascadeService           $retrieveCascade         Retrieve Cascade.
     * @param FraudService             $fraudService            Fraud Service.
     * @param NuDataService            $nuDataService           NuData Service.
     * @param PurchaseProcessHandler   $purchaseProcessHandler  Purchase Process Handler
     * @param BILoggerService          $biLoggerService         BI Logger Service.
     * @param PurchaseInitDTOAssembler $dtoAssembler            Http Command DTO Assembler.
     * @param BundleValidationService  $bundleValidationService Bundle Validation Service.
     * @param EventIngestionService    $eventIngestionService   Event ingestion service.
     * @param ConfigService            $configServiceClient     Config service client.
     */
    public function __construct(
        CascadeService $retrieveCascade,
        FraudService $fraudService,
        NuDataService $nuDataService,
        PurchaseProcessHandler $purchaseProcessHandler,
        BILoggerService $biLoggerService,
        PurchaseInitDTOAssembler $dtoAssembler,
        BundleValidationService $bundleValidationService,
        EventIngestionService $eventIngestionService,
        ConfigService $configServiceClient
    ) {
        $this->cascadeService          = $retrieveCascade;
        $this->fraudService            = $fraudService;
        $this->nuDataService           = $nuDataService;
        $this->purchaseProcessHandler  = $purchaseProcessHandler;
        $this->biLoggerService         = $biLoggerService;
        $this->dtoAssembler            = $dtoAssembler;
        $this->bundleValidationService = $bundleValidationService;
        $this->eventIngestionService   = $eventIngestionService;
        $this->configServiceClient     = $configServiceClient;
    }


    /**
     * @param PurchaseInitCommand $command The init command
     * @return void
     * @throws InvalidAmountException
     * @throws \Throwable
     * @throws LoggerException
     */
    protected function initPurchaseProcess(PurchaseInitCommand $command): void
    {
        $this->purchaseProcess = $this->createPurchaseProcess($command);
        $purchaseData          = $this->extractPurchasedItemsDataFromCommand($command);
        $this->purchaseProcess->initializeItem($purchaseData['mainPurchase']);

        $this->checkCrossSaleSites($purchaseData['crossSales']);

        foreach ($purchaseData['crossSales'] as $crossSale) {
            $this->purchaseProcess->initializeItem($crossSale, true);
        }

        $this->purchaseProcess->validateInitData();

        $this->bundleValidationService->validateBundleAddon(
            $this->purchaseProcess->initializedItemCollection()
        );
    }

    /**
     * @param array $crossSaleOptions Cross sale options.
     * @return void
     * @throws CrossSaleSiteNotExistException
     */
    protected function checkCrossSaleSites(array $crossSaleOptions): void
    {
        if (!empty($crossSaleOptions)) {
            $crossSaleSiteIds = $this->getUniqueCrossSaleSiteIds($crossSaleOptions);
            $this->validateCrossSaleSiteIds($crossSaleSiteIds);
        }
    }

    /**
     * @param array $crossSaleOptions Cross sale options.
     * @return array
     */
    public function getUniqueCrossSaleSiteIds(array $crossSaleOptions): array
    {
        $crossSaleSiteIds = [];
        foreach ($crossSaleOptions as $crossSaleOption) {
            $crossSaleSiteIds[] = $crossSaleOption['siteId'];
        }

        return array_unique($crossSaleSiteIds);
    }

    /**
     * @param array $crossSaleSiteIds Cross sale site ids.
     * @return void
     * @throws \Exception
     * @throws CrossSaleSiteNotExistException
     */
    public function validateCrossSaleSiteIds(array $crossSaleSiteIds): void
    {
        foreach ($crossSaleSiteIds as $crossSaleSiteId) {
            $crossSaleSite = $this->configServiceClient->getSite($crossSaleSiteId);

            $this->handleEmptyCrossSaleSite($crossSaleSiteId, $crossSaleSite);
        }
    }

    /**
     * @param string    $siteId Site id.
     * @param Site|null $site   Site.
     * @return void
     * @throws CrossSaleSiteNotExistException
     * @throws LoggerException
     */
    public function handleEmptyCrossSaleSite(string $siteId, ?Site $site): void
    {
        if (empty($site)) {
            throw new CrossSaleSiteNotExistException($siteId);
        }
    }

    /**
     * @param string      $sessionId         Session Id
     * @param string      $siteId            Site Id
     * @param string      $businessGroupId   Business Group Id
     * @param string      $country           Country
     * @param string      $paymentType       Payment type
     * @param string|null $paymentMethod     Payment method
     * @param string|null $trafficSource     Traffic source
     * @param string|null $forceCascade      Force Cascade
     * @param string|null $initialJoinBiller Biller used at initial join
     * @return void
     * @throws LoggerException
     * @throws MissingRedirectUrlException
     * @throws InvalidForceCascadeException
     * @throws UnknownBillerNameException
     */
    protected function setCascade(
        string $sessionId,
        string $siteId,
        string $businessGroupId,
        string $country,
        string $paymentType,
        ?string $paymentMethod,
        ?string $trafficSource,
        ?string $forceCascade = null,
        ?string $initialJoinBiller = null
    ): void {
        Log::info(
            'Retrieving cascade for the following parameters: ',
            [
                'sessionId'         => $sessionId,
                'siteId'            => $siteId,
                'businessGroupId'   => $businessGroupId,
                'country'           => $country,
                'paymentType'       => $paymentType,
                'paymentMethod'     => $paymentMethod,
                'trafficSource'     => $trafficSource,
                'forceCascade'      => $forceCascade,
                'initialJoinBiller' => $initialJoinBiller,
            ]
        );

        $cascade = $this->cascadeService->retrieveCascade(
            $sessionId,
            $siteId,
            $businessGroupId,
            $country,
            $paymentType,
            $paymentMethod,
            $trafficSource,
            $forceCascade,
            $initialJoinBiller
        );

        $this->checkRedirectUrlForThirdPartyBiller(
            $cascade->firstBiller()->isThirdParty(),
            $this->purchaseProcess->redirectUrl()
        );

        if ($cascade->firstBiller() instanceof BillerAvailablePaymentMethods) {
            $cascade->firstBiller()->addPaymentMethod($paymentMethod);
        }

        $this->purchaseProcess->setCascade($cascade);
    }

    /**
     * @param PurchaseInitCommand $command            PurchaseInitCommand
     * @param bool                $isThirdPartyBiller Is the first biller third party
     * @return void
     * @throws LoggerException
     * @throws Exception
     * @throws IllegalStateTransitionException
     */
    protected function setFraudAdvice(
        PurchaseInitCommand $command,
        bool $isThirdPartyBiller
    ): void {
        if ($isThirdPartyBiller || !$this->shouldSetFraudAdvice($command->site())) {
            $this->fraudAdviceNotRequired((string) $command->site()->siteId());

            return;
        }

        $this->retrieveFraudAdvice($command);
    }

    /**
     * @param PurchaseInitCommand $command Purchase Init Command
     *
     * @return void
     * @throws LoggerException
     */
    protected function setNuDataSettings(PurchaseInitCommand $command): void
    {
        Log::info('Setting in PurchaseProcess NuData settings');

        try {
            if ($command->paymentType() !== CCPaymentInfo::PAYMENT_TYPE) {
                return;
            }

            $nuDataSettings = $this->nuDataService->retrieveSettings(
                (string) $command->site()->businessGroupId()
            );
            $this->purchaseProcess->setNuDataSettings($nuDataSettings);
        } catch (NuDataNotFoundException $exception) {
            Log::error($exception->getMessage());
        }
    }

    /**
     * @param PurchaseInitCommand $command The init command
     *
     * @return PurchaseProcess
     *
     * @throws LoggerException
     * @throws \Exception
     * @throws \Throwable
     */
    protected function createPurchaseProcess(PurchaseInitCommand $command): PurchaseProcess
    {
        Log::info('Creating the PurchaseProcess object');

        try {
            return PurchaseProcess::create(
                SessionId::createFromString($command->sessionId()),
                AtlasFields::create($command->atlasCode(), $command->atlasData()),
                $command->publicKeyIndex(),
                UserInfo::create(
                    CountryCode::create($command->clientCountryCode()),
                    Ip::create($command->clientIp())
                ),
                PaymentInfoFactoryService::create(
                    $command->paymentType(),
                    $command->paymentMethod()
                ),
                new InitializedItemCollection(),
                $command->memberId(),
                $command->entrySiteId(),
                CurrencyCode::create($command->currency()),
                $command->redirectUrl(),
                $command->postbackUrl(),
                $command->trafficSource(),
                $command->skipVoid()
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new UnableToCreatePurchaseProcessException();
        }
    }

    /**
     * @param PurchaseInitCommand $command The init command
     * @return array
     * @throws LoggerException
     */
    private function extractPurchasedItemsDataFromCommand(PurchaseInitCommand $command): array
    {
        $data['crossSales']   = [];
        $data['mainPurchase'] = [
            'siteId'         => (string) $command->site()->siteId(),
            'bundleId'       => $command->bundleId(),
            'addonId'        => $command->addOnId(),
            'subscriptionId' => $command->subscriptionId(),
            'amount'         => $command->amount(),
            'initialDays'    => $command->initialDays(),
            'rebillDays'     => $command->rebillDays(),
            'rebillAmount'   => $command->rebillAmount(),
            'isTrial'        => $command->isTrial(),
            'tax'            => $command->tax(),
        ];

        foreach ($command->crossSales() as $crossSale) {
            $data['crossSales'][] = $crossSale;
        }

        Log::info('Purchase items extracted from command: ', $data);

        return $data;
    }

    /**
     * @param Site $site Site
     * @return bool
     */
    protected function shouldSetFraudAdvice(Site $site): bool
    {
        return $site->isFraudServiceEnabled();
    }

    /**
     * Note: This method in this abstract class was used to keep
     * backward compatibility. There is a flag  in app to set this compatibility.
     * We have been using the method in the extended class in the real flow.
     *
     * @param PurchaseInitCommand $command Purchase Init Command
     * @return void
     */
    protected function retrieveFraudAdvice(PurchaseInitCommand $command): void
    {
        try {
            $fraudAdvices = $this->fraudService->retrieveAdvice(
                $command->site()->siteId(),
                ['ip' => $command->clientIp()],
                FraudAdvice::FOR_INIT,
                SessionId::createFromString((string) $this->purchaseProcess->sessionId())
            );

            $this->purchaseProcess->setFraudAdvice($fraudAdvices);
            $this->purchaseProcess->setFraudRecommendation(
                FraudIntegrationMapper::mapFraudAdviceToFraudRecommendation($fraudAdvices)
            );
            $this->detectThreeDUsage();
            $this->purchaseProcess->initStateAccordingToFraudAdvice();
        } catch (\Exception $exception) {
            $this->purchaseProcess->setFraudAdvice(FraudAdvice::create());
            $this->purchaseProcess->setFraudRecommendation(FraudRecommendation::createDefaultAdvice());
        }
    }

    /**
     * @param string $siteId The site id
     * @return void
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws LoggerException
     */
    protected function fraudAdviceNotRequired(string $siteId): void
    {
        Log::info(
            'Fraud is disabled for the site, skipping retrieval of advice',
            ['siteId' => $siteId]
        );
        $this->purchaseProcess->setFraudAdvice(FraudAdvice::create());
        $this->purchaseProcess->setFraudRecommendation(FraudRecommendation::createDefaultAdvice());
        $this->purchaseProcess->validate();
    }

    /**
     * @return void
     */
    protected function detectThreeDUsage(): void
    {
        if ($this->purchaseProcess->fraudAdvice() === null || $this->purchaseProcess->cascade() === null) {
            return;
        }

        if (!$this->purchaseProcess->fraudAdvice()->isForceThreeD()
            && $this->purchaseProcess->cascade()->firstBiller()->isThreeDSupported()
        ) {
            $this->purchaseProcess->fraudAdvice()->markDetectThreeDUsage();
        }
    }

    /**
     * @return void
     * @throws LoggerException
     * @throws \Exception
     */
    protected function shipBiInitializedPurchaseEvent(): void
    {
        Log::info('Begin creating the BiInitializedPurchaseEvent');

        $mainPurchaseItem = $this->purchaseProcess->retrieveMainPurchaseItem()->toArray();
        //this flag is not required for BI events
        unset($mainPurchaseItem['isCrossSale']);

        $crossSaleItems = $this->purchaseProcess->retrieveInitializedCrossSales();

        $crossSalesArray = [];

        /** @var InitializedItem $crossSaleItem */
        foreach ($crossSaleItems as $crossSaleItem) {
            $data = $crossSaleItem->toArray();
            //this flag is not required for BI events
            unset($data['isCrossSale']);
            $crossSalesArray[] = $data;
        }

        $paymentTemplateCollection = $this->purchaseProcess->paymentTemplateCollection();

        $paymentTemplates = null;

        if ($paymentTemplateCollection instanceof PaymentTemplateCollection) {
            $paymentTemplates = [];
            foreach ($paymentTemplateCollection as $paymentTemplate) {
                $data               = $paymentTemplate->toArray();
                $paymentTemplates[] = $data;
            }
        }

        $threeD              = null;
        $gatewayServiceFlags = [];

        if ($this->purchaseProcess->cascade()->firstBiller()->isThreeDSupported()) {
            $threeD = [
                'forceThreeD'       => $this->purchaseProcess->fraudAdvice()->isForceThreeDOnInit(),
                'detectThreeDUsage' => $this->purchaseProcess->fraudAdvice()->isDetectThreeDUsage(),
                'version'           => 1
            ];
        }

        if ($this->purchaseProcess->cascade() && !empty($this->purchaseProcess->cascade()->removedBillersFor3DS())) {
            $gatewayServiceFlags['overrideCascadeNetbillingReason3ds'] = $this->purchaseProcess->cascade()
                ->removedBillersFor3DS()
                ->contains(NetbillingBiller::BILLER_NAME);
        } else {
            $gatewayServiceFlags['overrideCascadeNetbillingReason3ds'] = false;
        }

        $fraudCollection = $this->purchaseProcess->fraudRecommendationCollection()
            ? $this->purchaseProcess->fraudRecommendationCollection()->toArray()
            : null;

        $purchaseInitializedEvent = $this->generatePurchaseInitializedEvent(
            $mainPurchaseItem,
            $crossSalesArray,
            $paymentTemplates,
            $threeD,
            $fraudCollection,
            $gatewayServiceFlags
        );
        $this->biLoggerService->write($purchaseInitializedEvent);
        if (config('app.feature.event_ingestion_communication.send_general_bi_events')) {
            $this->eventIngestionService->queue($purchaseInitializedEvent);
        }
    }

    /**
     * @param array      $mainPurchaseItem    Main Purchased Item
     * @param array      $crossSalesArray     Cross sale
     * @param array|null $paymentTemplates    Payment Template
     * @param array|null $threeD              ThreeD
     * @param array|null $fraudCollection     Fraud Collection
     * @param array|null $gatewayServiceFlags Gateway Service Flag
     * @return PurchaseInitialized
     */
    protected function generatePurchaseInitializedEvent(
        array $mainPurchaseItem,
        array $crossSalesArray,
        ?array $paymentTemplates,
        ?array $threeD,
        ?array $fraudCollection,
        ?array $gatewayServiceFlags
    ): PurchaseInitialized {
        return new PurchaseInitialized(
            (string) $this->purchaseProcess->sessionId(),
            $mainPurchaseItem,
            $crossSalesArray,
            (string) $this->purchaseProcess->userInfo()->ipAddress(),
            (string) $this->purchaseProcess->currency(),
            $this->purchaseProcess->paymentInfo()->paymentType(),
            (string) $this->purchaseProcess->userInfo()->countryCode(),
            $this->purchaseProcess->memberId(),
            $this->purchaseProcess->entrySiteId(),
            $paymentTemplates,
            $this->purchaseProcess->atlasFields()->atlasCodeDecoded(),  // BG-37030 send to BI event
            $this->purchaseProcess->fraudRecommendation() ? $this->purchaseProcess->fraudRecommendation()
                ->toArray() : null,
            $threeD,
            $this->purchaseProcess->paymentMethod(),
            $this->purchaseProcess->trafficSource(),
            $fraudCollection,
            $gatewayServiceFlags,
            self::selectedCascadeInfo($this->purchaseProcess->cascade())
        );
    }

    /**
     * @param bool        $isThirdPartyBiller Is the biller a third party one
     * @param string|null $redirectUrl        Redirect url
     * @return void
     * @throws MissingRedirectUrlException
     */
    protected function checkRedirectUrlForThirdPartyBiller(bool $isThirdPartyBiller, ?string $redirectUrl): void
    {
        if (!empty($redirectUrl) || !$isThirdPartyBiller) {
            return;
        }

        throw new MissingRedirectUrlException(
            RestartProcess::create()->toArray()
        );
    }

    /**
     * @param null|Cascade $cascade Cascade
     * @return array
     */
    private static function selectedCascadeInfo(?Cascade $cascade): array
    {
        $selectedCascadeInfo = [];
        if (!empty($cascade)) {
            $billers = $cascade->billers();
            foreach ($billers as $key => $biller) {
                $selectedCascadeInfo[] = [
                    "submitNumber"           => (int) $key + 1,
                    "numberOfAllowedSubmits" => $biller->maxSubmits(),
                    "billerName"             => $biller->name()
                ];;
            }
        }

        return $selectedCascadeInfo;
    }
}
