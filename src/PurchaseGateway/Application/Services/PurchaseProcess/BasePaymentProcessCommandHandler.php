<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess;

use ProBillerNG\BI\BILoggerService;
use ProBillerNG\BI\Event\BaseEvent;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\BI\FraudPurchaseVelocity;
use ProBillerNG\PurchaseGateway\Application\BI\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\PostbackResponseDto;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseGeneralHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Exceptions\BlockedDueToFraudException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionExpiredException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\UnknownBillerNameException;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler as SessionHandler;
use ProBillerNG\PurchaseGateway\Application\Services\BaseCommandHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonId;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\BinRoutingCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleId;
use ProBillerNG\PurchaseGateway\Domain\Model\CCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\CountryCode;
use ProBillerNG\PurchaseGateway\Domain\Model\FirstName;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\LastName;
use ProBillerNG\PurchaseGateway\Domain\Model\Password;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PhoneNumber;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\Username;
use ProBillerNG\PurchaseGateway\Domain\Repository\SiteRepositoryReadOnly;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudService;
use ProBillerNG\PurchaseGateway\Domain\Services\CascadeTranslatingService;
use ProBillerNG\PurchaseGateway\Domain\Services\BillerMappingService;
use ProBillerNG\PurchaseGateway\Domain\Model\Bin;
use ProBillerNG\PurchaseGateway\Domain\Model\ChequePaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Model\Zip;
use ProBillerNG\PurchaseGateway\Domain\Services\PurchaseService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionTranslatingService;
use ProBillerNG\PurchaseGateway\Domain\Services\PostbackService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\LaravelBinRoutingServiceFactory;
use ProBillerNG\PurchaseGateway\Application\BI\FraudPurchaseVelocityEventDispatcher;

abstract class BasePaymentProcessCommandHandler extends BaseCommandHandler
{
    public const VISA                                 = 'VISA';
    public const MASTERCARD                           = 'MASTERCARD';
    public const DISCOVER                             = 'DISCOVER';
    public const JCB                                  = 'JCB';
    public const AMERICAN_EXPRESS                     = 'AMEX';
    public const MIR                                  = 'MIR';
    public const UNIONPAY                             = 'UNIONPAY';
    public const MAXIMUM_CC_BLACKLIST_CHECKS_ATTEMPTS = 2;

    /**
     * @var PurchaseProcess
     */
    protected $purchaseProcess;

    /**
     * @var SessionHandler
     */
    protected $purchaseProcessHandler;

    /**
     * @var PurchaseService
     */
    protected $purchaseService;

    /**
     * @var SiteRepositoryReadOnly
     */
    protected $siteRepository;

    /**
     * @var CascadeTranslatingService
     */
    private $retrieveCascade;

    /**
     * @var FraudService
     */
    private $fraudService;

    /**
     * @var BillerMappingService
     */
    protected $billerMappingService;

    /**
     * @var PostbackService
     */
    protected $postbackService;

    /**
     * @var TransactionTranslatingService
     */
    protected $transactionService;

    /**
     * @var ProcessPurchaseDTOAssembler
     */
    protected $dtoAssembler;

    /**
     * @var BILoggerService
     */
    protected $biLoggerService;

    /**
     * @var LaravelBinRoutingServiceFactory
     */
    protected $binRoutingServiceFactory;

    /**
     * @var EventIngestionService
     */
    protected $eventIngestionService;

    /**
     * ExistingPaymentProcessCommandHandler constructor.
     * @param FraudService                    $fraudService             Fraud Service.
     * @param BillerMappingService            $billerMappingService     BillerMappingRetrievalOnPurchaseProcess
     * @param LaravelBinRoutingServiceFactory $binRoutingServiceFactory Bin routing translating service factory
     * @param CascadeTranslatingService       $retrieveCascade          RetrieveCascade
     * @param SessionHandler                  $purchaseProcessHandler   SessionHandler
     * @param PurchaseService                 $purchaseService          PurchaseService
     * @param ProcessPurchaseDTOAssembler     $dtoAssembler             ProcessPurchaseDTOAssembler
     * @param SiteRepositoryReadOnly          $siteRepository           SiteRepositoryReadOnly
     * @param PostbackService                 $postbackService          PostbackService
     * @param BILoggerService                 $biLoggerService          BILoggerService
     * @param TransactionService              $transactionService       The transaction translating service
     * @param EventIngestionService           $eventIngestionService    Event Service
     */
    public function __construct(
        FraudService $fraudService,
        BillerMappingService $billerMappingService,
        LaravelBinRoutingServiceFactory $binRoutingServiceFactory,
        CascadeTranslatingService $retrieveCascade,
        SessionHandler $purchaseProcessHandler,
        PurchaseService $purchaseService,
        ProcessPurchaseDTOAssembler $dtoAssembler,
        SiteRepositoryReadOnly $siteRepository,
        PostbackService $postbackService,
        BILoggerService $biLoggerService,
        TransactionService $transactionService,
        EventIngestionService $eventIngestionService
    ) {
        $this->fraudService             = $fraudService;
        $this->billerMappingService     = $billerMappingService;
        $this->retrieveCascade          = $retrieveCascade;
        $this->purchaseProcessHandler   = $purchaseProcessHandler;
        $this->purchaseService          = $purchaseService;
        $this->dtoAssembler             = $dtoAssembler;
        $this->siteRepository           = $siteRepository;
        $this->postbackService          = $postbackService;
        $this->biLoggerService          = $biLoggerService;
        $this->transactionService       = $transactionService;
        $this->binRoutingServiceFactory = $binRoutingServiceFactory;
        $this->eventIngestionService    = $eventIngestionService;
    }

    /**
     * @return void
     * @throws LoggerException
     * @throws SessionExpiredException
     */
    protected function checkIfPurchaseHasBeenAlreadyProcessed(): void
    {
        if ($this->purchaseProcess->isProcessed()) {
            //TODO add specialized exception
            throw new SessionExpiredException();
        }
    }

    /**
     * @return void
     * @throws SessionExpiredException
     * @throws LoggerException
     */
    protected function checkIfTheMaximumAttemptsUsingABlacklistedCardWasReached(): void
    {
        if ($this->purchaseProcess->creditCardWasBlacklisted()
            && $this->purchaseProcess->gatewaySubmitNumber() == self::MAXIMUM_CC_BLACKLIST_CHECKS_ATTEMPTS
        ) {
            throw new SessionExpiredException();
        }
    }

    /**
     * @return void
     * @throws BlockedDueToFraudException
     * @throws LoggerException
     */
    protected function checkIfPurchaseCanBeProcessedDueToFraud(): void
    {
        if ($this->purchaseProcess->shouldBlockProcess()) {
            throw new BlockedDueToFraudException();
        }
    }

    /**
     * @param Email|null $email  Email
     * @param Bin|null   $bin    Bin
     * @param Zip|null   $zip    Zip
     * @param SiteId     $siteId SiteId
     * @return void
     * @throws LoggerException
     */
    protected function checkUserInput(?Email $email, ?Bin $bin, ?Zip $zip, SiteId $siteId): void
    {
        Log::info('Checking the user input');

        try {
            if (!$this->shouldCheckFraud($email, $bin, $zip)) {
                return;
            }

            $processFraudAdvice = $this->fraudService->retrieveAdvice(
                $siteId,
                $this->purchaseProcess->fraudAdvice()->getChangedFraudFields($email, $zip, $bin),
                FraudAdvice::FOR_PROCESS,
                $this->purchaseProcess->sessionId()
            );

            $this->purchaseProcess->setFraudAdvice(
                $this->purchaseProcess->fraudAdvice()->addProcessFraudAdvice($processFraudAdvice)
            );
        } catch (\Exception $e) {
            // Unable to check fraud for user, continue
            Log::info('Fraud check on user input failed');
            Log::logException($e);
        }
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
     * @param Email|null $email Email.
     * @param Bin        $bin   Bin.
     * @param Zip        $zip   Zip.
     * @return bool
     * @throws LoggerException
     */
    private function shouldCheckFraud(?Email $email, ?Bin $bin, ?Zip $zip): bool
    {
        Log::info('Captcha should be verified again if the advice was already given but the payload changed');

        // If the advice was already given but the payload changed captcha should be verified again
        return $this->purchaseProcess->fraudAdvice()->fraudFieldsChanged($email, $zip, $bin);
    }

    /**
     * @param ProcessPurchaseCommand $command The purchase command
     * @return void
     * @throws LoggerException
     * @throws \Throwable
     */
    protected function addUserInfoToPurchaseProcess(ProcessPurchaseCommand $command): void
    {
        Log::info('Adding the user info to the PurchaseProcess object instance');

        try {
            $this->purchaseProcess->userInfo()->setAddress($command->address());
            $this->purchaseProcess->userInfo()->setCity($command->city());
            $this->purchaseProcess->userInfo()->setState($command->state());
            $this->purchaseProcess->userInfo()->setEmail(Email::create($command->email()));
            $this->purchaseProcess->userInfo()->setFirstName(FirstName::create($command->firstName()));
            $this->purchaseProcess->userInfo()->setLastName(LastName::create($command->lastName()));
            $this->purchaseProcess->userInfo()->setPassword(Password::create($command->password()));
            $phoneNumber = null;
            if (!empty($command->phoneNumber())) {
                $phoneNumber = PhoneNumber::create($command->phoneNumber());
            }
            $this->purchaseProcess->userInfo()->setPhoneNumber($phoneNumber);
            $this->purchaseProcess->userInfo()->setUsername(Username::create($command->username()));
            $this->purchaseProcess->userInfo()->setZipCode(Zip::create($command->zip()));
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * @param Site          $site          Site
     * @param BillerMapping $billerMapping Biller mapping
     * @return BinRoutingCollection
     * @throws LoggerException
     * @throws UnknownBillerNameException
     */
    protected function retrieveRoutingCodes(
        Site $site,
        BillerMapping $billerMapping
    ): BinRoutingCollection {

        $binRoutingService = $this->binRoutingServiceFactory->get($billerMapping->billerName());

        try {
            $binRoutingCollection = $binRoutingService->retrieveRoutingCodes(
                $this->purchaseProcess,
                $this->purchaseProcess->retrieveMainPurchaseItem()->itemId(),
                $site,
                $billerMapping
            );

            Log::info(
                'Retrieved routing codes',
                [
                    'codes' => $binRoutingCollection instanceof BinRoutingCollection ? $binRoutingCollection->toArray() : null
                ]
            );

            return $binRoutingCollection;
        } catch (\Exception $e) {
            // Unable to retrieve routing codes, proceed without using routing codes
            Log::info('Bin Routing retrieval failed');
            Log::logException($e);

            return new BinRoutingCollection();
        }
    }

    /**
     * @param ProcessPurchaseCommand $command The process command
     * @return void
     * @throws \Exception
     */
    protected function checkSelectedCrossSales(ProcessPurchaseCommand $command): void
    {
        foreach ($command->crossSales() as $commandCrossSale) {
            /** @var InitializedItem $crossSales */
            $crossSales = $this->purchaseProcess->retrieveInitializedCrossSales();

            foreach ($crossSales as $crossSale) {
                $isValidatedCrossSale = $this->validateCrossSale($commandCrossSale, $crossSale);
                $isAcceptedCard       = $this->acceptedCard($command->ccNumber(), (string) $crossSale->siteId());
                if (!$isValidatedCrossSale || !$isAcceptedCard) {
                    Log::info('CrossSaleSkipped Cross sale for site ' . $crossSale->siteId() . ' was skipped.',
                        [
                            'isValidCrossSale' => $isValidatedCrossSale,
                            'isAcceptedCard '  => $isAcceptedCard,
                            'commandCrossSale' => $commandCrossSale,
                        ]
                    );

                    continue;
                }

                Log::info('Cross sale was selected for use', $commandCrossSale);

                /** @var InitializedItem $crossSale */
                $crossSale->markCrossSaleAsSelected();
            }
        }
    }

    /**
     * @param ProcessPurchaseGeneralHttpDTO $dto Process Purchase General Http DTO
     * @return PostbackResponseDto
     */
    protected function buildDtoPostback(ProcessPurchaseGeneralHttpDTO $dto): PostbackResponseDto
    {
        return PostbackResponseDto::createFromResponseData(
            $dto,
            $dto->tokenGenerator(),
            $this->purchaseProcess->publicKeyIndex(),
            $this->purchaseProcess->sessionId(),
            $this->purchaseProcess->retrieveMainPurchaseItem(),
            $this->purchaseProcess->retrieveProcessedCrossSales()
        );
    }

    /**
     * @param Site $site Site
     *
     * @return string|null
     */
    protected function getPostbackUrl(Site $site): ?string
    {
        $postbackUrl = $this->purchaseProcess->postbackUrl();

        if (!empty($postbackUrl)) {
            return $postbackUrl;
        }

        return $site->postbackUrl();
    }

    /**
     * @param array           $commandCrossSale The selected cross sale
     * @param InitializedItem $initializedItem  cross sale object
     * @return bool
     * @throws \Exception
     */
    private function validateCrossSale(array $commandCrossSale, InitializedItem $initializedItem): bool
    {
        return AddonId::createFromString($commandCrossSale['addonId'])->equals($initializedItem->addonId())
               && BundleId::createFromString($commandCrossSale['bundleId'])->equals($initializedItem->bundleId())
               && SiteId::createFromString($commandCrossSale['siteId'])->equals($initializedItem->siteId());
    }

    /**
     * @param Site $site site
     * @return void
     * @throws LoggerException
     */
    protected function shipBiProcessedPurchaseEvent(Site $site): void
    {
        $purchaseBiEvent = $this->generatePurchaseBiEvent();

        $this->biLoggerService->write($purchaseBiEvent);
        if (config('app.feature.event_ingestion_communication.send_general_bi_events')) {
            $this->eventIngestionService->queue($purchaseBiEvent);
        }

        $paymentInfo = $this->purchaseProcess->paymentInfo();

        if (self::shouldTriggerFraudVelocityEvent($purchaseBiEvent, $site, $paymentInfo)) {
            $fraudVelocityEvent = FraudPurchaseVelocity::createFromPurchaseProcessed(
                $purchaseBiEvent,
                $this->purchaseProcess->userInfo()->ipAddress(),
                $site,
                $paymentInfo,
                !empty($this->purchaseProcess->paymentTemplateCollection()) ? $this->purchaseProcess->paymentTemplateCollection()
                    ->isSafeSelectedTemplate() : null
            );
            $this->dispatchFraudVelocityEvent($this->eventIngestionService, $fraudVelocityEvent, $paymentInfo);
        }
    }

    /**
     * @param BaseEvent   $purchaseBiEvent Purchase BI event.
     * @param Site        $site            Site.
     * @param PaymentInfo $paymentInfo     Payment info.
     *
     * @return bool
     * @throws LoggerException
     */
    public static function shouldTriggerFraudVelocityEvent(
        BaseEvent $purchaseBiEvent,
        Site $site,
        PaymentInfo $paymentInfo
    ): bool {

        $featureEnabled = config('app.feature.event_ingestion_communication.send_fraud_velocity_event');
        if (!$featureEnabled) {
            Log::info(
                'ShipBiProcessedPurchase should not send the fraud velocity event because the send_fraud_velocity_event config is set to false'
            );
            return false;
        }

        if (!$site->isFraudServiceEnabled()) {
            Log::info(
                'ShipBiProcessedPurchase should not send the fraud velocity event because fraud service is disabled for site',
                ['siteId' => $site->id()]
            );

            return false;
        }

        if (!$purchaseBiEvent instanceof PurchaseProcessed) {
            Log::info(
                'ShipBiProcessedPurchase should not send the fraud velocity event because the event is not PurchaseProcessed'
            );
            return false;
        }

        if ($paymentInfo->paymentType() !== CCPaymentInfo::PAYMENT_TYPE && $paymentInfo->paymentType() !== ChequePaymentInfo::PAYMENT_TYPE) {
            Log::info(
                'ShipBiProcessedPurchase should not send the fraud velocity event because the paymentType is not CC or Cheque',
                ['paymentType' => $paymentInfo->paymentType()]
            );
            return false;
        }

        Log::info(
            'ShipBiProcessedPurchase should trigger the fraud velocity event'
        );

        return true;
    }

    /**
     * @return BaseEvent
     */
    abstract protected function generatePurchaseBiEvent(): BaseEvent;

    /**
     * @param EventIngestionService $eventIngestionService
     * @param FraudPurchaseVelocity $fraudVelocityEvent
     * @param PaymentInfo           $paymentInfo
     *
     * @return void
     */
    private function dispatchFraudVelocityEvent(
        EventIngestionService $eventIngestionService,
        FraudPurchaseVelocity $fraudVelocityEvent,
        PaymentInfo $paymentInfo
    ): void {
        FraudPurchaseVelocityEventDispatcher::dispatchFraudVelocityEvent($eventIngestionService, $this->biLoggerService, $fraudVelocityEvent, $paymentInfo);
    }

    /**
     * Cards that should be allowed only for blacklisted domains.
     *
     * @param string $cardNumber Card Number
     * @param string $siteId     Site UUID
     *
     * @return bool
     * @throws LoggerException
     */
    protected function acceptedCard($cardNumber, $siteId): bool
    {
        $binNumber = substr($cardNumber, 0, 6);

        $visaCreditCardBlacklist = [
            '299b14d0-cf3d-11e9-8c91-0cc47a283dd2', // www.pornhubpremium.com
            '75e8dd61-565b-4a59-9121-b99514eda32d', // www.adult.com
            'b8e9f9d4-bd17-47e3-ac9c-04261a0c1904', // BrazzersPlus.com
            '6a212adc-f06d-4416-92b1-6fc2e0a520a9', // modelhub.com
            '299fa5cc-cf3d-11e9-8c91-0cc47a283dd2', // redtubepremium.com
            '299f959d-cf3d-11e9-8c91-0cc47a283dd2', // youpornpremium.com
        ];

        $masterCreditCardBlacklist = [
            '299b14d0-cf3d-11e9-8c91-0cc47a283dd2', // www.pornhubpremium.com
            '6a212adc-f06d-4416-92b1-6fc2e0a520a9', // modelhub.com
            '299fa5cc-cf3d-11e9-8c91-0cc47a283dd2', // redtubepremium.com
            '299f959d-cf3d-11e9-8c91-0cc47a283dd2', // youpornpremium.com
        ];

        Log::debug('Checking if card is accepted.', ['binNumber' => $binNumber, 'domain' => $siteId]);

        $cardBrand = $this->getCardBrand($binNumber);

        if (($cardBrand === self::VISA && in_array($siteId, $visaCreditCardBlacklist))
            || ($cardBrand === self::MASTERCARD && in_array($siteId, $masterCreditCardBlacklist))
        ) {
            Log::warning(
                'Payment method not supported.',
                [
                    'binNumber'                 => $binNumber,
                    'cardBrand'                 => $cardBrand,
                    'domain'                    => $siteId,
                    'visaCreditCardBlacklist'   => $visaCreditCardBlacklist,
                    'masterCreditCardBlacklist' => $masterCreditCardBlacklist,
                ]
            );

            return false;
        }

        return true;
    }

    /**
     * Returns the card brand for a given card number.
     *
     * Sources:
     * - https://en.wikipedia.org/wiki/Payment_card_number
     * - https://www.discovernetworkvar.com/common/pdf/var/9-2_VAR_ALERT_Sep_2009.pdf
     *
     * @param string $binNumber First six digits of the card number.
     *
     * @return string
     */
    private function getCardBrand($binNumber)
    {
        $cardBrand = '';

        // We need to make sure we have an integer to do the comparison later.
        $binNumber = intval($binNumber);

        // IIN (Issuer Identification Number) Ranges specification.
        $iinRanges = [
            self::VISA             => [
                [400000, 499999],
            ],
            self::MASTERCARD       => [
                [510000, 559999],
                [222100, 272099],
            ],
            self::DISCOVER         => [
                [601100, 601109],
                [601120, 601149],
                [601174, 601174], // This range should be 60117400-60117499, but we are not able to handle 8 digits.
                [601177, 601179],
                [601186, 601199],
                [644000, 659999],
            ],
            self::JCB              => [
                [352800, 358999],
            ],
            self::AMERICAN_EXPRESS => [
                [370000, 379999],
            ],
            self::MIR => [
                [220000, 220099],
                [220100, 220199],
                [220200, 220299],
                [220300, 220399],
                [220400, 220499],
            ],
            self::UNIONPAY => [
                [620000, 629999],
                [880000, 889999],
                [810000, 8171999],
            ]
        ];

        // First we go over all brands and get the possible ranges.
        foreach ($iinRanges as $brand => $ranges) {
            // Then we check each range.
            foreach ($ranges as $range) {
                // Once we find, we stop.
                if ($binNumber >= $range[0] && $binNumber <= $range[1]) {
                    $cardBrand = $brand;
                    break;
                }
            }
        }

        return $cardBrand;
    }
}
