<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\Lookup;

use ProBillerNG\Base\Application\Services\Command;
use ProBillerNG\Base\Domain\InvalidCommandException;
use ProBillerNG\BI\BILoggerService;
use ProBillerNG\BI\Event\BaseEvent;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\BI\Purchase3DSLookup;
use ProBillerNG\PurchaseGateway\Application\BI\PurchaseBiEventFactory;
use ProBillerNG\PurchaseGateway\Application\DTO\Lookup\LookupThreeDDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\Exceptions\MissingRedirectUrlException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionAlreadyProcessedException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionNotFoundException;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\ManageCreditCardBlacklistTrait;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\BasePaymentProcessCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\RedisHelperTrait;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler as SessionHandler;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InitPurchaseInfoNotFoundException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidCreditCardExpirationDate;
use ProBillerNG\PurchaseGateway\Domain\Model\InMemoryRepository;
use ProBillerNG\PurchaseGateway\Domain\Model\NewCCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\RestartProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Services\CCForBlackListService;
use ProBillerNG\PurchaseGateway\Domain\Services\PostbackService;
use ProBillerNG\PurchaseGateway\Domain\Services\PurchaseService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;

class LookupThreeDCommandHandler extends BasePaymentProcessCommandHandler
{
    use RedisHelperTrait, ManageCreditCardBlacklistTrait;

    /**
     * @var LookupThreeDDTOAssembler
     */
    protected $assembler;

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
     * @var InMemoryRepository
     */
    private $redisRepository;

    /**
     * LookupThreeDCommandHandler constructor.
     *
     * @param LookupThreeDDTOAssembler $assembler              Lookup DTO Assembler.
     * @param SessionHandler           $purchaseProcessHandler Session Handler
     * @param TransactionService       $transactionService     Transaction service
     * @param BILoggerService          $biLoggerService        Bi logger service
     * @param CryptService             $cryptService           Crypt service
     * @param TokenGenerator           $tokenGenerator         Token generator
     * @param PurchaseService          $purchaseService        Purchase service
     * @param PostbackService          $postbackService        Postback service
     * @param EventIngestionService    $eventIngestionService  Event Service
     * @param InMemoryRepository       $redisRepository        The Redis interface
     * @param CCForBlackListService    $CCForBlackListService  Credit card for blacklist service
     */
    public function __construct(
        LookupThreeDDTOAssembler $assembler,
        SessionHandler $purchaseProcessHandler,
        TransactionService $transactionService,
        BILoggerService $biLoggerService,
        CryptService $cryptService,
        TokenGenerator $tokenGenerator,
        PurchaseService $purchaseService,
        PostbackService $postbackService,
        EventIngestionService $eventIngestionService,
        InMemoryRepository $redisRepository,
        CCForBlackListService $CCForBlackListService
    ) {
        $this->assembler              = $assembler;
        $this->purchaseProcessHandler = $purchaseProcessHandler;
        $this->transactionService     = $transactionService;
        $this->biLoggerService        = $biLoggerService;
        $this->eventIngestionService  = $eventIngestionService;
        $this->cryptService           = $cryptService;
        $this->tokenGenerator         = $tokenGenerator;
        $this->purchaseService        = $purchaseService;
        $this->postbackService        = $postbackService;
        $this->redisRepository        = $redisRepository;

        $this->init($CCForBlackListService);
    }

    /**
     * @param Command $command Command
     *
     * @return mixed|\ProBillerNG\PurchaseGateway\Application\DTO\Lookup\LookupThreeDHttpDTO
     *
     * @throws InvalidCommandException
     * @throws InvalidCreditCardExpirationDate
     * @throws MissingRedirectUrlException
     * @throws SessionAlreadyProcessedException
     * @throws SessionNotFoundException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\DuplicatedPurchaseProcessRequestException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidCurrency
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidPaymentInfoException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\PurchaseEntityCannotBeCreatedException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentMethodException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentTypeException
     * @throws \Throwable
     */
    public function execute(Command $command)
    {
        if (!$command instanceof LookupThreeDCommand) {
            throw new InvalidCommandException(LookupThreeDCommand::class, $command);
        }

        // Call the helper for redis which will validate based on the sessionId:
        //   - If the key exists and the value is "processing", then throw "DuplicatedPurchaseProcessRequestException"
        //   - If the key does not exist, then store the key and continue the flow
        $this->isProcessAlreadyStarted($command->sessionId());

        try {

            $this->purchaseProcess = $this->purchaseProcessHandler->load((string) $command->sessionId());

            $this->validateSession();
            $this->setPaymentInfo($command);

            $transactionInfo = $this->transactionService->lookupTransaction(
                $this->purchaseProcess->retrieveMainPurchaseItem(),
                $this->purchaseProcess->paymentInfo(),
                $this->purchaseProcess->retrieveProcessedCrossSales(),
                $command->site(),
                $this->purchaseProcess->fraudAdvice(),
                $this->purchaseProcess->userInfo(),
                $this->createRedirectUrl(),
                $command->deviceFingerprintId(),
                $command->site()->isNsfSupported()
            );

            // BG-48689: When we start a 3DS2 flow there is a case scenario when Rocketgate switches to 3DS.
            // When we do the lookup with device fingerprint Rocketgate switches to 3DS and sends us the acs and pareq.
            // Because the status of purchase is changed to ThreeDLookupPerformed, when trying to authenticate, if fails
            // due to session already processed. Since the process status is already PENDING, we should leave it in the
            // same state if purchase was switched to 3DS.
            $isSwitchedTo3DS = !empty($transactionInfo->acs()) && !empty($transactionInfo->pareq());

            // In case of a frictionless transaction there wont be an ACS, nor PAREQ.
            // We will still need to setup the stage performThreeDLookup so the purchase can pass through
            if (($transactionInfo->threeDVersion() && !$isSwitchedTo3DS) || $transactionInfo->threeDFrictionless()) {
                $this->purchaseProcess->performThreeDLookup();
            }

            // Call config service for a possible CC blacklist
            $creditCardWasBlacklisted = $this->blacklistCreditCardIfNeeded(
                $this->purchaseProcess->retrieveMainPurchaseItem(),
                $this->purchaseProcess->retrieveProcessedCrossSales(),
                substr($this->purchaseProcess->paymentInfo()->ccNumber(), 0, 6),
                substr($this->purchaseProcess->paymentInfo()->ccNumber(), -4),
                (string) $this->purchaseProcess->paymentInfo()->expirationMonth(),
                (string) $this->purchaseProcess->paymentInfo()->expirationYear(),
                (string) $this->purchaseProcess->sessionId()
            );

            $this->purchaseProcess->setCreditCardWasBlacklisted($creditCardWasBlacklisted);

            if (!$transactionInfo->isPending() && $transactionInfo->isNsf()) {
                $this->purchaseProcess->postProcessing();
            } elseif (!$transactionInfo->isPending()) {
                $this->purchaseProcess->postProcessing();
            }

            $this->purchase = $this->purchaseService->createPurchaseEntity(
                $this->purchaseProcess,
                $command->site()
            );

            $dto = $this->assembler->assemble($this->purchaseProcess, $command->site());

            if ($this->purchaseProcess->isProcessed()) {
                $this->postbackService->queue($this->buildDtoPostback($dto), $this->getPostbackUrl($command->site()));
            }

            // Ship BI event
            $this->shipBiProcessedPurchaseEvent($command->site());

            return $dto;

        } catch (InitPurchaseInfoNotFoundException $ex) {
            throw new SessionNotFoundException($ex);
        } catch (\Exception $ex) {
            Log::logException($ex);
            throw $ex;
        } finally {
            if ($this->purchaseProcess !== null) {
                $this->purchaseProcess->incrementGatewaySubmitNumberIfValid();

                // Store the purchase process
                $this->purchaseProcessHandler->update($this->purchaseProcess);

                // Remove the redis key as the process has finished and all data have been stored
                $this->removeKeyOfFinishedProcess($command->sessionId());
            }
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

        return route('threed.complete', ['jwt' => $jwt]);
    }

    /**
     * @param LookupThreeDCommand $command The process command
     * @return void
     * @throws \Exception
     * @throws InvalidCreditCardExpirationDate
     * @throws \Throwable
     */
    protected function setPaymentInfo(LookupThreeDCommand $command): void
    {
        Log::info('Setting the payment info type cc');

        $paymentInfo = NewCCPaymentInfo::create(
            $command->ccNumber(),
            $command->cvv(),
            $command->expirationMonth(),
            $command->expirationYear(),
            $this->purchaseProcess->paymentMethod()
        );

        $this->purchaseProcess->setPaymentInfo($paymentInfo);
    }

    /**
     * @return BaseEvent
     * @throws \Exception
     */
    protected function generatePurchaseBiEvent(): BaseEvent
    {
        if ($this->purchaseProcess->wasMainItemPurchasePending()) {
            /**
             * @var Transaction $lastTransaction
             */
            $lastTransaction = $this->purchaseProcess->retrieveMainPurchaseItem()->lastTransaction();
            return new Purchase3DSLookup(
                (string) $this->purchaseProcess->sessionId(),
                (int) $lastTransaction->threeDVersion()
            );
        }
        return PurchaseBiEventFactory::createForNewCC($this->purchaseProcess);
    }

    /**
     * @throws MissingRedirectUrlException
     * @throws SessionAlreadyProcessedException
     * @throws \ProBillerNG\Logger\Exception
     */
    protected function validateSession(): void
    {
        if (empty($this->purchaseProcess->redirectUrl())) {
            throw new MissingRedirectUrlException(
                RestartProcess::create()->toArray()
            );
        }

        if (!$this->purchaseProcess->isPending()) {
            throw new SessionAlreadyProcessedException(
                (string) $this->purchaseProcess->sessionId(),
                RestartProcess::create()->toArray(),
                $this->purchaseProcess->redirectUrl()
            );
        }
    }
}
