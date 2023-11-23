<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyRedirect;

use ProBillerNG\BI\BILoggerService;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\BI\PurchaseRedirectedToThirdParty;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseGeneralHttpDTO;
use ProBillerNG\PurchaseGateway\Application\DTO\ThirdPartyRedirect\ThirdPartyRedirectDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\ThirdPartyRedirect\ThirdPartyRedirectHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Exceptions\BillerMappingException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\MissingRedirectUrlException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidQueryException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionAlreadyProcessedException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionNotFoundException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SiteNotExistException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\TransactionAlreadyProcessedException as TransactionAlreadyProcessed;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler as SessionHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\AttemptTransactionData;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerAvailablePaymentMethods;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerForCurrentSubmit;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InitPurchaseInfoNotFoundException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidPaymentTemplateId;
use ProBillerNG\PurchaseGateway\Domain\Model\ExistingCCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\FirstName;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\LastName;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\RestartProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\Biller;
use ProBillerNG\PurchaseGateway\Domain\Repository\SiteRepositoryReadOnly;
use ProBillerNG\PurchaseGateway\Domain\Services\BillerMappingService;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\TransactionAlreadyProcessedException;
use ProBillerNG\PurchaseGateway\Domain\Services\MemberProfileGatewayService;
use ProBillerNG\PurchaseGateway\Domain\Services\PaymentInfoFactoryService;
use ProBillerNG\PurchaseGateway\Domain\Services\PaymentTemplateService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;

class ThirdPartyRedirectQueryHandler
{
    /**
     * @var BILoggerService
     */
    private $biLoggerService;

    /**
     * @var SessionHandler
     */
    private $purchaseProcessHandler;

    /**
     * @var ThirdPartyRedirectDTOAssembler
     */
    private $assembler;

    /**
     * @var BillerMappingService
     */
    private $billerMappingService;

    /**
     * @var PurchaseProcess
     */
    protected $purchaseProcess;

    /** @var ConfigService */
    private $configServiceClient;

    /**
     * @var TransactionService
     */
    protected $transactionService;

    /**
     * @var CryptService
     */
    private $cryptService;

    /**
     * @var TokenGenerator
     */
    private $tokenGenerator;

    /**
     * @var EventIngestionService
     */
    private $eventIngestionService;

    /**
     * @var PaymentTemplateService
     */
    private $paymentTemplateService;

    /**
     * @var MemberProfileGatewayService
     */
    private $memberProfileGatewayService;

    /**
     * ThirdPartyRedirectQueryHandler constructor.
     *
     * @param ThirdPartyRedirectDTOAssembler $assembler                   Third party redirect Assembler.
     * @param SessionHandler                 $purchaseProcessHandler      Session Handler.
     * @param BillerMappingService           $billerMappingService        Biller Mapping Service.
     * @param ConfigService                  $configServiceClient
     * @param TransactionService             $transactionService          Transaction service.
     * @param BILoggerService                $biLoggerService             Bi logger service.
     * @param CryptService                   $cryptService                Crypt service.
     * @param TokenGenerator                 $tokenGenerator              Token generator.
     * @param EventIngestionService          $eventIngestionService       Event service.
     * @param PaymentTemplateService         $paymentTemplateService      Payment template service.
     * @param MemberProfileGatewayService    $memberProfileGatewayService Member profile gateway service
     */
    public function __construct(
        ThirdPartyRedirectDTOAssembler $assembler,
        SessionHandler $purchaseProcessHandler,
        BillerMappingService $billerMappingService,
        ConfigService $configServiceClient,
        TransactionService $transactionService,
        BILoggerService $biLoggerService,
        CryptService $cryptService,
        TokenGenerator $tokenGenerator,
        EventIngestionService $eventIngestionService,
        PaymentTemplateService $paymentTemplateService,
        MemberProfileGatewayService $memberProfileGatewayService
    ) {
        $this->assembler                   = $assembler;
        $this->purchaseProcessHandler      = $purchaseProcessHandler;
        $this->billerMappingService        = $billerMappingService;
        $this->biLoggerService             = $biLoggerService;
        $this->configServiceClient         = $configServiceClient;
        $this->transactionService          = $transactionService;
        $this->cryptService                = $cryptService;
        $this->tokenGenerator              = $tokenGenerator;
        $this->eventIngestionService       = $eventIngestionService;
        $this->paymentTemplateService      = $paymentTemplateService;
        $this->memberProfileGatewayService = $memberProfileGatewayService;
    }

    /**
     * @param ThirdPartyRedirectQuery $query Query
     * @return ThirdPartyRedirectHttpDTO|ProcessPurchaseGeneralHttpDTO
     * @throws \Exception
     */
    public function execute(ThirdPartyRedirectQuery $query)
    {
        if (!$query instanceof ThirdPartyRedirectQuery) {
            throw new InvalidQueryException(ThirdPartyRedirectQuery::class, $query);
        }

        try {
            $this->purchaseProcess = $this->purchaseProcessHandler->load((string) $query->sessionId());

            if (empty($this->purchaseProcess->redirectUrl())) {
                throw new MissingRedirectUrlException(
                    RestartProcess::create()->toArray()
                );
            }

            if (!$this->purchaseProcess->isPending()) {
                $redirectUrl = $this->purchaseProcess->redirectUrl();
                $this->clearProcessSession();

                throw new SessionAlreadyProcessedException(
                    (string) $query->sessionId(),
                    RestartProcess::create()->toArray(),
                    $redirectUrl
                );
            }

            $mainPurchase = $this->purchaseProcess->retrieveMainPurchaseItem();
            $site         = $this->configServiceClient->getSite((string) $mainPurchase->siteId());

            if ($site === null) {
                throw new SiteNotExistException();
            }

            $billerForNextTransactionAttempts = BillerForCurrentSubmit::create(
                $this->purchaseProcess->cascade(),
                null
            );

            $biller = $billerForNextTransactionAttempts->biller();

            $billerMapping  = $this->retrieveBillerMapping(
                $site,
                $biller
            );
            $billerMemberId = $this->retrieveBillerMemberId($biller->name());

            $this->updateUserInfo($site);

            $this->transactionService->performThirdPartyTransaction(
                $this->purchaseProcess->retrieveMainPurchaseItem(),
                $this->purchaseProcess->retrieveInitializedCrossSales(),
                $site,
                $this->retrieveSiteForCrossSales(),
                $biller,
                AttemptTransactionData::create(
                    $this->purchaseProcess->currency(),
                    $this->purchaseProcess->userInfo(),
                    $this->purchaseProcess->paymentInfo()
                ),
                $billerMapping,
                $this->createRedirectUrl(),
                $this->purchaseProcess->paymentMethod(),
                $billerMemberId ?? null
            );

            $this->purchaseProcess->redirect();

            /**
             * @var Transaction $transaction
             */
            $transaction = $this->purchaseProcess->retrieveMainPurchaseItem()->transactionCollection()->last();

            if (!$transaction->isPending()) {
                $this->purchaseProcess->finishProcessingOrValidate();
                $this->purchaseProcess->incrementGatewaySubmitNumberIfValid();

                return $this->assembler->assemble(
                    $this->purchaseProcess,
                    $site
                );
            }

            $redirectBiEvent = new PurchaseRedirectedToThirdParty(
                (string) $query->sessionId(),
                (string) $this->purchaseProcess->state()
            );

            $this->biLoggerService->write($redirectBiEvent);
            if (config('app.feature.event_ingestion_communication.send_general_bi_events')) {
                $this->eventIngestionService->queue($redirectBiEvent);
            }

            return $this->assembler->assemble(
                $this->purchaseProcess,
                $site
            );
        } catch (InitPurchaseInfoNotFoundException $ex) {
            throw new SessionNotFoundException($ex);
        } catch (TransactionAlreadyProcessedException $ex) {
            $redirectUrl = $this->purchaseProcess->redirectUrl();
            throw new TransactionAlreadyProcessed(
                RestartProcess::create()->toArray(),
                $redirectUrl ?? ""
            );
        } catch (\Exception $ex) {
            Log::logException($ex);
            throw $ex;
        } finally {
            if ($this->purchaseProcess !== null) {
                // Store the purchase process
                $this->purchaseProcessHandler->update($this->purchaseProcess);
            }
        }
    }

    /**
     * @param Site   $site   Site
     * @param Biller $biller Biller
     * @return BillerMapping
     * @throws BillerMappingException
     * @throws Exception
     */
    protected function retrieveBillerMapping(Site $site, Biller $biller): BillerMapping
    {
        try {
            return $this->billerMappingService->retrieveBillerMapping(
                $biller->name(),
                (string) $site->businessGroupId(),
                (string) $site->siteId(),
                (string) $this->purchaseProcess->currency(),
                (string) $this->purchaseProcess->sessionId()
            );
        } catch (\Exception $e) {
            Log::info('Unable to retrieve biller fields');
            throw new BillerMappingException($e);
        }
    }

    /**
     * @return string
     */
    protected function createRedirectUrl(): string
    {
        $jwt = (string) $this->tokenGenerator->generateWithGenericKey(
            [
                'sessionId' => $this->cryptService->encrypt((string) $this->purchaseProcess->sessionId())
            ]
        );

        return route('thirdParty.return', ['jwt' => $jwt]);
    }


    /**
     * @return array
     * @throws SiteNotExistException
     * @throws Exception
     */
    private function retrieveSiteForCrossSales(): array
    {
        $sites = [];
        /**
         * @var InitializedItem $crossSale
         */
        foreach ($this->purchaseProcess->retrieveInitializedCrossSales() as $crossSale) {
            $site = $this->configServiceClient->getSite((string) $crossSale->siteId());

            if (is_null($site)) {
                throw new SiteNotExistException();
            }

            $sites[(string) $crossSale->itemId()] = $site;
        }

        return $sites;
    }

    /**
     * @return void
     */
    private function clearProcessSession(): void
    {
        $this->purchaseProcess = null;
    }

    /**
     * @param string $billerName Biller name.
     * @return string|null
     * @throws Exception
     * @throws InvalidPaymentTemplateId
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidPaymentInfoException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentMethodException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentTypeException
     */
    private function retrieveBillerMemberId(string $billerName): ?string
    {
        if ($this->purchaseProcess->isCurrentBillerAvailablePaymentsMethods()) {
            return $this->purchaseProcess->memberId();
        }

        $paymentTemplateCollection = $this->purchaseProcess->paymentTemplateCollection();

        if ($paymentTemplateCollection === null) {
            return null;
        }

        $lastUsedPaymentTemplateFromSession = $paymentTemplateCollection->getLastUsedBillerTemplate($billerName);

        if ($lastUsedPaymentTemplateFromSession === null) {
            return null;
        }

        $lastUsedPaymentTemplate = $this->paymentTemplateService->retrieveThirdPartyBillerPaymentTemplate(
            $this->purchaseProcess,
            [
                'paymentTemplateId' => $lastUsedPaymentTemplateFromSession->templateId()
            ]
        );

        $paymentInfo = PaymentInfoFactoryService::create(
            $this->purchaseProcess->paymentInfo()->paymentType(),
            $this->purchaseProcess->paymentMethod(),
            $lastUsedPaymentTemplate->billerFields()['memberId'],
            $lastUsedPaymentTemplate->templateId()
        );

        $this->purchaseProcess->setPaymentInfo($paymentInfo);

        return $lastUsedPaymentTemplate->billerFields()['memberId'] ?? null;
    }

    /**
     * @param Site $site Site
     * @return void
     * @throws Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoPassword
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoUsername
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoFirstName
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoLastName
     */
    private function updateUserInfo(Site $site): void
    {
        if ($this->purchaseProcess->cascade()->currentBiller() instanceof BillerAvailablePaymentMethods) {
            return;
        }

        if ($this->purchaseProcess->wasMemberIdGenerated()) {
            return;
        }

        if (!$this->purchaseProcess->cascade()->currentBiller() instanceof BillerAvailablePaymentMethods) {
            if ($this->purchaseProcess->wasMemberIdGenerated()) {
                $this->purchaseProcess->generateOrUpdateUser();
            }

            if ($this->purchaseProcess->userInfo()->username()) {
                return;
            }
        }

        $memberInfo = $this->memberProfileGatewayService->retrieveMemberProfile(
            (string) $this->purchaseProcess->memberId(),
            $site->id(),
            $site->publicKeys()[0],
            (string) $this->purchaseProcess->sessionId(),
            $this->purchaseProcess->mainPurchaseSubscriptionId(),
            $this->purchaseProcess->entrySiteId()
        );

        $this->purchaseProcess->generateOrUpdateUser(
            (string) $memberInfo->username(),
            (string) $memberInfo->email()
        );

        if ($this->purchaseProcess->cascade()->currentBiller() instanceof BillerAvailablePaymentMethods) {
            $this->purchaseProcess->userInfo()->setEmail($memberInfo->email());
            $this->purchaseProcess->userInfo()->setFirstName(FirstName::create($memberInfo->firstName()));
            $this->purchaseProcess->userInfo()->setLastName(LastName::create($memberInfo->lastName()));
        }
    }
}
